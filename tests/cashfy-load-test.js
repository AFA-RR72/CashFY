
import http from 'k6/http';
import { sleep, check } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

// ==========================================
// CASHFY LOAD TEST 2.0
// ==========================================

const BASE_URL = __ENV.BASE_URL || 'http://localhost/activities/CashFY/';

const pageDuration = new Trend('cashfy_page_duration');
const pageErrors = new Rate('cashfy_page_errors');
const pageRequests = new Counter('cashfy_page_requests');

export const options = {
    scenarios: {
        cashfy_load: {
            executor: 'constant-vus',
            vus: 150,
            duration: '70m',
        },
    },

    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<2000'],
        checks: ['rate>0.99'],
    },
};

// Rotas reais encontradas no projeto
const pages = [
    { path: 'index.php', name: 'home', weight: 40 },

    { path: 'MVC/view/categoria.php?cat=1', name: 'category', weight: 5 },
    { path: 'MVC/view/categoria.php?cat=2', name: 'category', weight: 5 },
    { path: 'MVC/view/categoria.php?cat=3', name: 'category', weight: 5 },
    { path: 'MVC/view/categoria.php?cat=4', name: 'category', weight: 5 },
    { path: 'MVC/view/categoria.php?cat=5', name: 'category', weight: 5 },
    { path: 'MVC/view/categoria.php?cat=6', name: 'category', weight: 5 },

    { path: 'MVC/view/sobre.php', name: 'about', weight: 10 },
    { path: 'MVC/view/contact.php', name: 'contact', weight: 10 },
    { path: 'MVC/view/tos.php', name: 'terms', weight: 5 },
    { path: 'MVC/view/pp.php', name: 'privacy', weight: 5 },
];

function choosePage() {
    const random = Math.random() * 100;
    let accumulated = 0;

    for (const page of pages) {
        accumulated += page.weight;

        if (random < accumulated) {
            return page;
        }
    }

    return pages[0];
}

export default function () {
    const page = choosePage();
    const url = BASE_URL + page.path;

    const response = http.get(url, {
        tags: {
            page: page.name,
        },
        timeout: '30s',
    });

    const validStatus = response.status === 200;
    const fastResponse = response.timings.duration < 2000;

    check(response, {
        'HTTP 200': () => validStatus,
        'Resposta abaixo de 2s': () => fastResponse,
    });

    pageDuration.add(response.timings.duration, {
        page: page.name,
    });

    pageErrors.add(!validStatus, {
        page: page.name,
    });

    pageRequests.add(1, {
        page: page.name,
    });

    // Tempo de navegação entre páginas
    sleep(Math.random() * 2 + 1);
}

export function handleSummary(data) {
    return {
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
        'cashfy-load-summary.json': JSON.stringify(data, null, 2),
    };
}
