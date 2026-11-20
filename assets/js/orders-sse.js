/*
 * Sistema integrado de Web Push Notifications (Service Worker + Push API + VAPID + PWA iOS)
 * e atualizações em tempo real na página via SSE (EventSource).
 */
(function () {
    if (window.__cashfyOrdersSseInitialized) {
        return;
    }
    window.__cashfyOrdersSseInitialized = true;

    const notifyEl = document.getElementById('orders-notify');
    const badge = document.getElementById('orders-badge');
    const bottomBadge = document.getElementById('bottom-nav-orders-badge');
    const config = notifyEl || badge;

    if (!config) {
        return;
    }

    const userId = Number(config.dataset.userId || 0);
    const endpoint = config.dataset.endpoint;
    const pushEndpoint = config.dataset.pushEndpoint || '';
    const vapidPublicKey = config.dataset.vapidPublicKey || '';
    const ordersUrl = config.dataset.ordersUrl || 'perfil.php?orders';
    const myOrdersUrl = config.dataset.myOrdersUrl || 'myOrders.php';
    const swUrl = config.dataset.swUrl;

    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent)
        || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches
        || window.navigator.standalone === true;

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    const swRegistrationPromise = ('serviceWorker' in navigator && swUrl && window.isSecureContext)
        ? navigator.serviceWorker.register(swUrl)
            .then(() => navigator.serviceWorker.ready)
            .catch(error => {
                console.error('Erro ao registrar Service Worker:', error);
                return null;
            })
        : Promise.resolve(null);

    async function syncPushSubscription() {
        if (!window.isSecureContext || !('PushManager' in window) || !pushEndpoint) {
            return false;
        }
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return false;
        }

        try {
            const registration = await swRegistrationPromise;
            if (!registration || !registration.pushManager) {
                return false;
            }

            let publicKey = vapidPublicKey;
            if (!publicKey) {
                const keyResp = await fetch(pushEndpoint, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    credentials: 'same-origin'
                });
                const keyJson = await keyResp.json().catch(() => null);
                if (keyJson && keyJson.ok && keyJson.publicKey) {
                    publicKey = keyJson.publicKey;
                }
            }

            if (!publicKey) {
                return false;
            }

            let subscription = await registration.pushManager.getSubscription();
            if (!subscription) {
                subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array(publicKey)
                });
            }

            const resp = await fetch(pushEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ subscription: subscription.toJSON() })
            });

            return resp.ok;
        } catch (error) {
            console.error('Erro ao sincronizar inscrição Web Push:', error);
            return false;
        }
    }

    const notice = document.getElementById('orders-update');
    const buyerNotice = document.getElementById('buyer-orders-update');
    const renderedKey = (config.dataset.pending || '0') + '-' + (config.dataset.lastId || '0');
    const initialBuyerHash = config.dataset.buyerHash || '';

    const SELLER_STORAGE_KEY = 'cashfy:orders:lastNotified:' + userId;
    const BUYER_STORAGE_KEY = 'cashfy:buyerOrders:state:' + userId;

    let knownSellerLastId = Number(config.dataset.lastId) || 0;

    function storedSellerLastNotified() {
        try {
            return Number(localStorage.getItem(SELLER_STORAGE_KEY)) || 0;
        } catch (e) {
            return 0;
        }
    }

    function storeSellerLastNotified(id) {
        try {
            localStorage.setItem(SELLER_STORAGE_KEY, String(id));
        } catch (e) {}
    }

    function loadBuyerStateMap() {
        try {
            const raw = localStorage.getItem(BUYER_STORAGE_KEY);
            if (raw) {
                const parsed = JSON.parse(raw);
                if (parsed && typeof parsed === 'object') {
                    return parsed;
                }
            }
        } catch (e) {}
        return null;
    }

    function saveBuyerStateMap(map) {
        try {
            localStorage.setItem(BUYER_STORAGE_KEY, JSON.stringify(map));
        } catch (e) {}
    }

    let buyerKnownMap = loadBuyerStateMap();
    if (!buyerKnownMap) {
        buyerKnownMap = {};
        try {
            const initialOrders = JSON.parse(config.dataset.buyerOrders || '[]');
            initialOrders.forEach(function (o) {
                buyerKnownMap[String(o.id)] = {
                    status: o.status,
                    explanation: o.explanation || ''
                };
            });
            saveBuyerStateMap(buyerKnownMap);
        } catch (e) {
            buyerKnownMap = {};
        }
    }

    function setBadge(pending) {
        const count = Number(pending) || 0;
        [badge, bottomBadge].forEach(function (el) {
            if (!el) return;
            el.textContent = count > 0 ? String(count) : '';
            el.hidden = count <= 0;
        });
    }

    function money(value) {
        return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function describeSellerOrder(data) {
        const latest = data.latest || {};
        let text = latest.id ? 'Pedido #' + latest.id : 'Você recebeu um pedido';

        if (latest.buyer) {
            text += ' de ' + latest.buyer;
        }

        if (latest.total !== undefined && latest.total !== null) {
            text += ' — ' + money(latest.total);
        }

        return text;
    }

    function showToast(options) {
        const old = document.getElementById('order-toast');
        if (old) {
            old.remove();
        }

        const toast = document.createElement('a');
        toast.id = 'order-toast';
        toast.className = 'order-toast' + (options.variant ? ' is-' + options.variant : '');
        toast.href = options.href || ordersUrl;
        toast.setAttribute('role', 'status');

        const title = document.createElement('strong');
        title.textContent = options.title || 'Atualização de pedido';

        const body = document.createElement('span');
        body.textContent = options.text || '';

        toast.append(title, body);

        if (options.reason) {
            const reasonEl = document.createElement('small');
            reasonEl.className = 'order-toast-reason';
            reasonEl.textContent = 'Motivo: ' + options.reason;
            toast.append(reasonEl);
        }

        document.body.appendChild(toast);

        setTimeout(function () {
            if (toast.parentNode) {
                toast.remove();
            }
        }, options.duration || 9000);
    }

    async function sendBrowserNotification(title, body, tag, targetUrl) {
        if (!('Notification' in window) || Notification.permission !== 'granted') {
            return;
        }

        try {
            const registration = await swRegistrationPromise;
            if (!registration) {
                return;
            }

            const existingSub = registration.pushManager
                ? await registration.pushManager.getSubscription()
                : null;
            if (existingSub) {
                return;
            }

            await registration.showNotification(title, {
                body: body,
                tag: tag,
                renotify: true,
                vibrate: [200, 100, 200],
                data: { url: new URL(targetUrl, location.href).href }
            });
        } catch (error) {
            console.error('Erro ao exibir notificação:', error);
        }
    }

    async function notifyNewSellerOrder(data) {
        const text = describeSellerOrder(data);
        const id = (data.latest && data.latest.id) || data.last_id;

        showToast({
            title: 'Novo pedido recebido!',
            text: text,
            href: ordersUrl,
            variant: 'new'
        });

        await sendBrowserNotification(
            'Novo pedido no CashFY',
            text,
            'cashfy-seller-order-' + id,
            ordersUrl
        );
    }

    function updateOrderCardInDOM(order) {
        const card = document.querySelector('.mo-card[data-order-id="' + order.id + '"]');
        if (!card) {
            return false;
        }

        const statusMeta = {
            pending: { label: 'Aguardando vendedor', icon: 'fa-hourglass-half', group: 'progress' },
            accepted: { label: 'Aceito', icon: 'fa-circle-check', group: 'progress' },
            completed: { label: 'Concluído', icon: 'fa-flag-checkered', group: 'completed' },
            rejected: { label: 'Recusado', icon: 'fa-ban', group: 'rejected' }
        };

        const meta = statusMeta[order.status] || statusMeta.pending;
        card.dataset.status = order.status;
        card.dataset.group = meta.group;

        const badgeEl = card.querySelector('.mo-badge');
        if (badgeEl) {
            badgeEl.className = 'mo-badge is-' + order.status;
            badgeEl.innerHTML = '<i class="fa-solid ' + meta.icon + '" aria-hidden="true"></i> ' + meta.label;
        }

        const stepsList = card.querySelector('.mo-steps');
        if (order.status === 'rejected') {
            if (stepsList) {
                stepsList.hidden = true;
            }
        } else if (stepsList) {
            stepsList.hidden = false;
            const doneMap = { pending: 1, accepted: 2, completed: 3 };
            const doneCount = doneMap[order.status] || 1;
            const stepItems = stepsList.querySelectorAll('.mo-step');
            stepItems.forEach(function (li, idx) {
                const stepNum = idx + 1;
                li.classList.remove('is-done', 'is-current');
                li.removeAttribute('aria-current');
                if (stepNum <= doneCount) {
                    li.classList.add('is-done');
                } else if (stepNum === doneCount + 1) {
                    li.classList.add('is-current');
                    li.setAttribute('aria-current', 'step');
                }
            });
        }

        const oldNote = card.querySelector('.mo-note');
        if (oldNote) {
            oldNote.remove();
        }

        if (order.status === 'accepted') {
            const p = document.createElement('p');
            p.className = 'mo-note';
            p.innerHTML = '<i class="fa-solid fa-circle-info" aria-hidden="true"></i>' +
                '<span>O vendedor aceitou seu pedido! Combine a entrega ou a retirada com ele pelo WhatsApp. O pedido só aparece como concluído quando o vendedor finalizar, e os valores podem ser ajustados por ele nesse momento.</span>';
            card.appendChild(p);
        } else if (order.status === 'rejected') {
            const div = document.createElement('div');
            div.className = 'mo-note is-refused';
            div.setAttribute('role', 'note');

            const icon = document.createElement('i');
            icon.className = 'fa-solid fa-comment-slash';
            icon.setAttribute('aria-hidden', 'true');

            const content = document.createElement('div');
            const strong = document.createElement('strong');
            strong.textContent = 'Motivo da recusa';
            const p = document.createElement('p');
            p.textContent = (order.explanation && order.explanation.trim() !== '')
                ? order.explanation
                : 'O vendedor não informou o motivo.';

            content.append(strong, p);
            div.append(icon, content);
            card.appendChild(div);
        }

        return true;
    }

    async function notifyBuyerOrderStatusChange(order) {
        const isAccepted = order.status === 'accepted';
        const isRejected = order.status === 'rejected';
        const isCompleted = order.status === 'completed';

        if (!isAccepted && !isRejected && !isCompleted) {
            return;
        }

        let title = 'Atualização no seu pedido #' + order.id;
        let text = '';
        let variant = 'accepted';
        let reason = '';

        if (isAccepted) {
            title = 'Pedido #' + order.id + ' aceito!';
            text = (order.seller_name ? order.seller_name + ' aceitou seu pedido' : 'O vendedor aceitou seu pedido') +
                ' (' + money(order.total) + ').';
            variant = 'accepted';
        } else if (isRejected) {
            title = 'Pedido #' + order.id + ' recusado';
            text = (order.seller_name ? order.seller_name + ' recusou seu pedido.' : 'O vendedor recusou seu pedido.');
            reason = (order.explanation && order.explanation.trim() !== '')
                ? order.explanation.trim()
                : 'O vendedor não informou o motivo.';
            variant = 'rejected';
        } else if (isCompleted) {
            title = 'Pedido #' + order.id + ' concluído!';
            text = 'Sua compra com ' + (order.seller_name || 'o vendedor') + ' foi marcada como concluída.';
            variant = 'accepted';
        }

        updateOrderCardInDOM(order);

        if (buyerNotice) {
            buyerNotice.hidden = false;
        }

        showToast({
            title: title,
            text: text,
            reason: reason,
            href: myOrdersUrl,
            variant: variant,
            duration: isRejected ? 11000 : 8500
        });

        const pushBody = reason ? (text + ' Motivo: ' + reason) : text;
        await sendBrowserNotification(
            title,
            pushBody,
            'cashfy-buyer-order-' + order.id + '-' + order.status,
            myOrdersUrl
        );
    }

    const enableButton = document.getElementById('orders-notify-enable');
    const statusText = document.getElementById('orders-notify-status');

    function refreshPermissionUI() {
        if (!enableButton || !statusText) {
            return;
        }

        if (!window.isSecureContext) {
            enableButton.hidden = true;
            statusText.textContent = 'Web Push em segundo plano exige HTTPS. Você continuará recebendo avisos em tempo real dentro da página.';
        } else if (isIOS && !isStandalone) {
            enableButton.hidden = true;
            statusText.textContent = 'No iPhone/iPad (iOS 16.4+), toque em Compartilhar > "Adicionar à Tela de Início" e abra o app instalado para receber notificações com o site fechado.';
        } else if (!('Notification' in window) || !('serviceWorker' in navigator) || !('PushManager' in window)) {
            enableButton.hidden = true;
            statusText.textContent = 'Seu navegador não suporta Web Push Notifications.';
        } else if (Notification.permission === 'granted') {
            enableButton.hidden = true;
            statusText.textContent = 'Notificações ativadas neste dispositivo (inclusive com o site fechado).';
        } else if (Notification.permission === 'denied') {
            enableButton.hidden = true;
            statusText.textContent = 'Notificações bloqueadas. Libere nas permissões do site, no navegador.';
        } else {
            enableButton.hidden = false;
            statusText.textContent = 'Ative para receber alertas de pedidos mesmo com o navegador ou o site fechado.';
        }
    }

    let asking = false;

    async function requestPermissionAndSubscribe() {
        if (asking || !('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'granted') {
            await syncPushSubscription();
            refreshPermissionUI();
            return;
        }

        if (Notification.permission !== 'default') {
            refreshPermissionUI();
            return;
        }

        asking = true;
        try {
            const permission = await Notification.requestPermission();
            if (permission === 'granted') {
                await syncPushSubscription();
            }
        } catch (error) {
            console.error('Erro ao solicitar permissão de notificação:', error);
        } finally {
            asking = false;
            refreshPermissionUI();
        }
    }

    if (enableButton) {
        enableButton.addEventListener('click', requestPermissionAndSubscribe);
    }

    refreshPermissionUI();

    if ('Notification' in window && Notification.permission === 'granted') {
        syncPushSubscription();
    } else if ('Notification' in window && Notification.permission === 'default' && !isIOS) {
        ['pointerdown', 'keydown'].forEach(function (type) {
            document.addEventListener(type, requestPermissionAndSubscribe, { once: true });
        });
    }

    if (!window.EventSource || !endpoint) {
        return;
    }

    const source = new EventSource(endpoint);

    source.addEventListener('orders', function (event) {
        const data = JSON.parse(event.data);

        setBadge(data.pending);

        if (notice && data.pending + '-' + data.last_id !== renderedKey) {
            notice.hidden = false;
        }

        const threshold = Math.max(knownSellerLastId, storedSellerLastNotified());

        if (data.pending > 0 && data.last_id > threshold) {
            storeSellerLastNotified(data.last_id);
            notifyNewSellerOrder(data);
        }

        knownSellerLastId = Math.max(knownSellerLastId, data.last_id);
    });

    source.addEventListener('buyer_orders', function (event) {
        const data = JSON.parse(event.data);
        const orders = Array.isArray(data.orders) ? data.orders : [];

        const latestStored = loadBuyerStateMap() || buyerKnownMap;
        let hasChanges = false;

        orders.forEach(function (order) {
            const key = String(order.id);
            const prev = latestStored[key];

            if (!prev) {
                latestStored[key] = {
                    status: order.status,
                    explanation: order.explanation || ''
                };
                hasChanges = true;
                return;
            }

            if (prev.status !== order.status) {
                latestStored[key] = {
                    status: order.status,
                    explanation: order.explanation || ''
                };
                hasChanges = true;
                notifyBuyerOrderStatusChange(order);
            }
        });

        if (hasChanges) {
            buyerKnownMap = latestStored;
            saveBuyerStateMap(buyerKnownMap);
        }

        if (buyerNotice && initialBuyerHash && data.hash !== initialBuyerHash) {
            buyerNotice.hidden = false;
        }
    });

    window.addEventListener('beforeunload', function () {
        source.close();
    });
})();
