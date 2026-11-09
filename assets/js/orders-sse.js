/*
 * Pedidos do vendedor em tempo real, via SSE (EventSource).
 *
 * - Atualiza o contador de pendentes do menu "Pedidos" (quando existe, no perfil).
 * - Na aba Pedidos, mostra o aviso "Atualizar" quando a lista ficou desatualizada
 *   (a lista não recarrega sozinha para não apagar valores que o vendedor esteja editando).
 * - Notifica quando chega um pedido NOVO: notificação do navegador (se o vendedor
 *   permitiu e a aba não está em foco) ou aviso na própria página.
 *
 * A configuração vem de #orders-badge (perfil) ou de #orders-notify (demais páginas).
 */
(function () {
    const badge = document.getElementById('orders-badge');
    const config = badge || document.getElementById('orders-notify');

    if (!config || !window.EventSource) {
        return;
    }

    const endpoint = config.dataset.endpoint;
    const ordersUrl = config.dataset.ordersUrl || 'perfil.php?orders';
    const swUrl = config.dataset.swUrl;

    const swRegistrationPromise = ('serviceWorker' in navigator && swUrl)
        ? navigator.serviceWorker.register(swUrl)
            .then(() => navigator.serviceWorker.ready)
            .catch(error => {
                console.error('Erro ao registrar Service Worker:', error);
                return null;
            })
        : Promise.resolve(null);

    const notice = document.getElementById('orders-update');
    const renderedKey = config.dataset.pending + '-' + config.dataset.lastId;

    // Maior id de pedido que já foi avisado. Fica no localStorage para que várias
    // abas abertas ao mesmo tempo não notifiquem o mesmo pedido várias vezes.
    const STORAGE_KEY = 'cashfy:orders:lastNotified';
    let known = Number(config.dataset.lastId) || 0;

    function storedLastNotified() {
        try {
            return Number(localStorage.getItem(STORAGE_KEY)) || 0;
        } catch (e) {
            return 0;
        }
    }

    function storeLastNotified(id) {
        try {
            localStorage.setItem(STORAGE_KEY, String(id));
        } catch (e) { /* sem localStorage: segue sem deduplicar entre abas */ }
    }

    function setBadge(pending) {
        if (!badge) {
            return;
        }

        badge.textContent = pending > 0 ? pending : '';
        badge.hidden = pending <= 0;
    }

    function money(value) {
        return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    function describe(data) {
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

    // Aviso dentro da página (textContent: o nome do comprador nunca vira HTML)
    function showToast(text) {
        const old = document.getElementById('order-toast');
        if (old) {
            old.remove();
        }

        const toast = document.createElement('a');
        toast.id = 'order-toast';
        toast.className = 'order-toast';
        toast.href = ordersUrl;
        toast.setAttribute('role', 'status');

        const title = document.createElement('strong');
        title.textContent = 'Novo pedido!';

        const body = document.createElement('span');
        body.textContent = text;

        toast.append(title, body);
        document.body.appendChild(toast);

        setTimeout(function () {
            toast.remove();
        }, 8000);
    }

    function notifyNewOrder() {
        async function notifyNewOrder(data) {
            const text = describe(data);
            const id = data.latest?.id || data.last_id;

            // Mantém o aviso visual dentro do CashFY
            showToast(text);

            // Verifica a permissão
            if (!('Notification' in window) ||
                Notification.permission !== 'granted') {
                return;
            }

            try {
                const registration = await swRegistrationPromise;

                if (!registration) {
                    return;
                }

                await registration.showNotification('Novo pedido no CashFY', {
                    body: text,
                    tag: 'cashfy-order-' + id,
                    renotify: false,
                    vibrate: [200, 100, 200],
                    data: {
                        url: ordersUrl
                    }
                });

            } catch (error) {
                console.error('Erro ao exibir notificação:', error);
            }
        }
    }

    // Botão "Ativar notificações" (só existe na aba Pedidos)
    const enableButton = document.getElementById('orders-notify-enable');
    const statusText = document.getElementById('orders-notify-status');

    function refreshPermissionUI() {
        if (!enableButton || !statusText) {
            return;
        }

        if (!('Notification' in window)) {
            enableButton.hidden = true;
            statusText.textContent = 'Seu navegador não suporta notificações.';
        } else if (Notification.permission === 'granted') {
            enableButton.hidden = true;
            statusText.textContent = 'Notificações ativadas neste navegador.';
        } else if (Notification.permission === 'denied') {
            enableButton.hidden = true;
            statusText.textContent = 'Notificações bloqueadas. Libere nas permissões do site, no navegador.';
        } else {
            enableButton.hidden = false;
            statusText.textContent = 'Receba um aviso quando chegar um pedido novo.';
        }
    }

    if (enableButton) {
        enableButton.addEventListener('click', function () {
            Notification.requestPermission().then(refreshPermissionUI);
        });
    }

    refreshPermissionUI();

    const source = new EventSource(endpoint);

    source.addEventListener('orders', function (event) {
        const data = JSON.parse(event.data);

        setBadge(data.pending);

        if (notice && data.pending + '-' + data.last_id !== renderedKey) {
            notice.hidden = false;
        }

        // Só notifica pedido novo: id maior que qualquer um já conhecido ou avisado
        const threshold = Math.max(known, storedLastNotified());

        if (data.pending > 0 && data.last_id > threshold) {
            storeLastNotified(data.last_id);
            notifyNewOrder(data);
        }

        known = Math.max(known, data.last_id);
    });

    window.addEventListener('beforeunload', function () {
        source.close();
    });
})();
