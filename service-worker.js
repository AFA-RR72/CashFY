/*
 * Service Worker do CashFY (escopo raiz '/').
 * Recebe Web Push Notifications em segundo plano mesmo com todas as abas ou o navegador fechados
 * (Android, Desktop e iOS 16.4+ adicionado à Tela de Início) e abre a página do pedido ao clicar.
 */
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('push', event => {
    let payload = {
        title: 'CashFY',
        body: 'Você recebeu uma atualização nos seus pedidos.',
        url: './MVC/view/myOrders.php',
        tag: 'cashfy-push-notification'
    };

    if (event.data) {
        try {
            const parsed = event.data.json();
            if (parsed && typeof parsed === 'object') {
                payload = Object.assign(payload, parsed);
            }
        } catch (err) {
            const text = event.data.text();
            if (text) {
                payload.body = text;
            }
        }
    }

    const targetUrl = new URL(payload.url || './MVC/view/myOrders.php', self.registration.scope).href;
    const iconUrl = new URL(payload.icon || './assets/img/favicon-v2.png', self.registration.scope).href;
    const badgeUrl = new URL(payload.badge || './assets/img/favicon-v2.png', self.registration.scope).href;

    const options = {
        body: payload.body,
        icon: iconUrl,
        badge: badgeUrl,
        tag: payload.tag || 'cashfy-push-notification',
        renotify: true,
        requireInteraction: payload.status === 'rejected',
        vibrate: [200, 100, 200],
        data: {
            url: targetUrl,
            order_id: payload.order_id || null,
            status: payload.status || null,
            reason: payload.reason || null
        }
    };

    event.waitUntil(
        self.registration.showNotification(payload.title || 'CashFY', options)
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const targetUrl = event.notification.data?.url
        || new URL('./MVC/view/myOrders.php', self.registration.scope).href;

    event.waitUntil(
        self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clientList => {
            for (const client of clientList) {
                if ('focus' in client) {
                    if ('navigate' in client) {
                        return client.navigate(targetUrl).then(() => client.focus());
                    }
                    return client.focus();
                }
            }

            if (self.clients.openWindow) {
                return self.clients.openWindow(targetUrl);
            }
        })
    );
});

self.addEventListener('pushsubscriptionchange', event => {
    event.waitUntil(
        (async () => {
            try {
                const subEndpoint = new URL('./MVC/controller/push_subscription.php', self.registration.scope).href;
                const oldEndpoint = event.oldSubscription ? event.oldSubscription.endpoint : null;
                let newSubscription = event.newSubscription;

                if (!newSubscription && event.oldSubscription?.options?.applicationServerKey) {
                    newSubscription = await self.registration.pushManager.subscribe(event.oldSubscription.options);
                }

                if (newSubscription) {
                    await fetch(subEndpoint, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            subscription: newSubscription.toJSON(),
                            oldEndpoint: oldEndpoint
                        })
                    });
                }
            } catch (error) {
                console.error('Erro ao renovar subscription de Push:', error);
            }
        })()
    );
});
