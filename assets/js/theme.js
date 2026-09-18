const themeDesktop = document.getElementById('theme-toggle-desktop');
const themeMobile = document.getElementById('theme-toggle-mobile');

function toggleDarkMode(isDark) {
    document.body.classList.toggle('dark-mode', isDark);

    if (themeDesktop) {
        themeDesktop.checked = isDark;
    }

    if (themeMobile) {
        themeMobile.checked = isDark;
    }

    localStorage.setItem('theme', isDark ? 'dark' : 'light');
}

const savedTheme = localStorage.getItem('theme');
const isDark = savedTheme === 'dark';

toggleDarkMode(isDark);

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
console.log(document.getElementById("theme-toggle-desktop"));
console.log(document.getElementById("theme-toggle-mobile"));