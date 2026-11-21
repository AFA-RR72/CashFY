const themeDesktop = document.getElementById('theme-toggle-desktop');
const themeMobile = document.getElementById('theme-toggle-mobile');
const icon = document.getElementById('icon');
const apple_icon = document.getElementById('apple-icon');

function toggleDarkMode(isDark) {
    document.body.classList.toggle('dark-mode', isDark);

    if (themeDesktop) {
        themeDesktop.checked = isDark;
    }

    if (themeMobile) {
        themeMobile.checked = isDark;
    }

    const favicon = isDark
        ? 'assets/img/favicon-v2-dark.png'
        : 'assets/img/favicon-v2.png';

    if (icon) icon.href = favicon;
    if (apple_icon) apple_icon.href = favicon;

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