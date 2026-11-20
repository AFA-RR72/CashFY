import http from 'k6/http';
import { sleep, check } from 'k6';

export const options = {
    vus: 150,
    duration: '10m',
};

export default function () {
    const response = http.get('http://localhost/activities/CashFY/');

    check(response, {
        'Status 200': (r) => r.status === 200,
        'Resposta abaixo de 2 segundos': (r) => r.timings.duration < 2000,
    });

    sleep(1);
}
