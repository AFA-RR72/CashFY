// Captura dinamicamente a origem (protocolo + IP/host + porta se houver)
const BASE_URL = `${window.location.origin}/activities/CashFY/`;

function voltarPagina(event) {
    if (event) {
        event.preventDefault();
    }

    // Se a página anterior não pertence ao mesmo site/pasta, vai para a página inicial
    if (!document.referrer.startsWith(BASE_URL)) {
        window.location.href = BASE_URL + "index.php";
        return;
    }

    // Trata o histórico caso haja alteração por hash (#)
    if (window.location.hash) {
        history.go(-2);
    } else {
        history.back();
    }
}