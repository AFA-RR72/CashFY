const menuToggle = document.getElementById('menuToggle');
const navLinks = document.querySelector('.nav-links');

menuToggle.addEventListener('click', () => {
    menuToggle.classList.toggle('active');
    navLinks.classList.toggle('active');
});

navLinks.addEventListener('click', (event) => {
    if (event.target.tagName === 'A') {
        menuToggle.classList.remove('active');
        navLinks.classList.remove('active');
    }
});