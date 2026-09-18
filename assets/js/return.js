const BASE_URL = 'http://localhost/activities/CashFY/';

function voltarPagina(event) {
    event.preventDefault();

    if (!document.referrer.startsWith(BASE_URL)) {
        window.location.href = BASE_URL + 'index.php';
        return;
    }

    if (window.location.hash) {
        history.go(-2);
    } else {
        history.back();
    }
}