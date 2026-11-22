const themeDesktop = document.getElementById('theme-toggle-desktop');
const themeMobile = document.getElementById('theme-toggle-mobile');
const themeIcons = [
    document.getElementById('icon'),
    document.getElementById('icon-16'),
    document.getElementById('apple-icon')
].filter(Boolean);

function resolveThemeIconHref(el, isDark) {
    const target = isDark ? el.dataset.darkHref : el.dataset.lightHref;
    if (target) {
        return target;
    }

    const current = el.getAttribute('href') || '';
    const idx = current.lastIndexOf('/');
    const dir = idx >= 0 ? current.slice(0, idx + 1) : 'assets/img/';
    return dir + (isDark ? 'favicon-32x32-dark.png' : 'favicon-32x32.png');
}

function toggleDarkMode(isDark) {
    document.body.classList.toggle('dark-mode', isDark);

    if (themeDesktop) {
        themeDesktop.checked = isDark;
    }

    if (themeMobile) {
        themeMobile.checked = isDark;
    }

    themeIcons.forEach((el) => {
        const nextHref = resolveThemeIconHref(el, isDark);
        if (el.getAttribute('href') !== nextHref) {
            el.setAttribute('href', nextHref);
        }
    });

    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

const savedTheme = localStorage.getItem('theme');
toggleDarkMode(savedTheme === 'dark');

if (themeDesktop) {
    themeDesktop.addEventListener('change', () => {
        toggleDarkMode(themeDesktop.checked);
    });
}

if (themeMobile) {
    themeMobile.addEventListener('change', () => {
        toggleDarkMode(themeMobile.checked);
    });
}
