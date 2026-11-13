/*
 * Service Worker do CashFY. Fica na RAIZ do projeto de propósito: o escopo de um Service
 * Worker é a pasta onde ele está, então aqui ele cobre todas as páginas (antes ficava em
 * assets/js e a URL registrada nem existia).
 */
self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(self.clients.claim());
});

self.addEventListener('notificationclick', event => {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        }).then(clients => {
            for (const client of clients) {
                if ('focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }

            return self.clients.openWindow(url);
        })
    );
});