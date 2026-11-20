
const BASE_URL = `${window.location.origin}/activities/CashFY/`;

function voltarPagina(event) {
    event.preventDefault();

    const referrer = document.referrer;

    if (referrer) {
        const previousPage = new URL(referrer);

        const isCashFY =
            previousPage.origin === window.location.origin &&
            previousPage.pathname.startsWith('/activities/CashFY/') &&
            previousPage.pathname !== window.location.pathname;

        if (isCashFY) {
            history.back();
            return;
        }
    }

    window.location.href = BASE_URL + 'index.php';
}

// Navegação por âncoras sem modificar o histórico ou a URL
document.addEventListener('click', function (event) {
    const link = event.target.closest('a[href^="#"]');

    if (!link) return;

    event.preventDefault();

    const targetId = link.getAttribute('href').substring(1);

    if (!targetId) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
        return;
    }

    const target = document.getElementById(targetId);

    if (target) {
        target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
});
