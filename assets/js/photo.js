(function () {
    'use strict';

    var MAX_BYTES = 10 * 1024 * 1024; // mesmo limite do servidor (10 MB)

    /* Avisos de foto: somem sozinhos e podem ser fechados no X */
    document.querySelectorAll('[data-photo-toast]').forEach(function (notice) {
        var timer = setTimeout(dismiss, 4500);

        function dismiss() {
            clearTimeout(timer);
            notice.classList.add('is-leaving');

            setTimeout(function () {
                if (notice.parentNode) {
                    notice.parentNode.removeChild(notice);
                }
            }, 350);
        }

        var close = notice.querySelector('[data-photo-toast-close]');

        if (close) {
            close.addEventListener('click', dismiss);
        }
    });

    /* Campos de foto: valida o arquivo e atualiza o texto de ajuda */
    document.querySelectorAll('input[type="file"][data-photo-input]').forEach(function (input) {
        var hint = document.querySelector(input.getAttribute('data-photo-hint') || '#none');
        var icon = hint ? hint.querySelector('i') : null;
        var text = hint ? hint.querySelector('span') : null;
        var originalText = text ? text.textContent : '';
        var originalIcon = icon ? icon.className : '';

        function setHint(state, message) {
            if (!hint) { return; }

            hint.classList.remove('is-ready', 'is-error');

            if (state === 'ready') {
                hint.classList.add('is-ready');
                if (icon) { icon.className = 'fa-solid fa-circle-check'; }
            } else if (state === 'error') {
                hint.classList.add('is-error');
                if (icon) { icon.className = 'fa-solid fa-circle-exclamation'; }
            } else {
                if (icon) { icon.className = originalIcon; }
            }

            if (text) { text.textContent = message; }
        }

        input.addEventListener('change', function () {
            var file = input.files && input.files[0];

            if (!file) {
                setHint('idle', originalText);
                return;
            }

            if (file.type && file.type.indexOf('image/') !== 0) {
                input.value = '';
                setHint('error', 'O arquivo escolhido não é uma imagem.');
                return;
            }

            if (file.size > MAX_BYTES) {
                input.value = '';
                setHint('error', 'Essa imagem passa de 10 MB. Escolha uma foto menor.');
                return;
            }

            setHint('ready', input.getAttribute('data-hint-selected') || 'Foto selecionada.');
        });
    });
})();
