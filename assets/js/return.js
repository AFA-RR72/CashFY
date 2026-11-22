const BASE_URL = `${window.location.origin}/`;

function voltarPagina(event) {
    event.preventDefault();

    const referrer = document.referrer;

    if (referrer) {
        try {
            const previousPage = new URL(referrer);

            const currentURL = new URL(window.location.href);

            const isCashFY =
                previousPage.origin === currentURL.origin &&
                previousPage.pathname !== currentURL.pathname;

            if (isCashFY) {
                history.back();
                return;
            }
        } catch (error) {
            // URL inválida: utiliza o redirecionamento padrão.
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