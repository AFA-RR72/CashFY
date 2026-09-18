window.addEventListener('load', function () {
    if (window.location.hash === '#msg') {
        const msg = document.getElementById('msg');

        if (msg) {
            setTimeout(() => {
                msg.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            }, 100);
        }
    }
});