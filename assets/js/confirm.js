/*
 * Balão de confirmação reutilizável.
 *
 * Use o atributo data-confirm em um link <a>, em um botão de envio <button type="submit">
 * ou em um <button type="button"> dentro de um <form>:
 *
 *   data-confirm="Texto da pergunta"          (obrigatório, pode ser só "" para usar o padrão)
 *   data-confirm-title="Título"               (opcional)
 *   data-confirm-ok="Texto do botão"          (opcional, padrão "Confirmar")
 *   data-confirm-cancel="Texto do cancelar"   (opcional, padrão "Cancelar")
 *   data-confirm-icon="fa-trash-can"          (opcional, ícone Font Awesome)
 *   data-confirm-tone="neutral"               (opcional; padrão é vermelho/"perigo")
 *
 * Para pedir um motivo (ex.: recusar pedido) acrescente:
 *   data-reason-field="explanation"           nome do campo enviado ao servidor
 *   data-reason-label="Motivo da recusa"      (opcional)
 *   data-reason-placeholder="..."             (opcional)
 *   data-reason-min="5"  data-reason-max="255"
 *
 * Ao confirmar: link -> navega; botão de envio -> envia o formulário com o name/value do botão.
 */
(function () {
    let overlay = null;
    let lastFocus = null;
    let current = null;

    function el(tag, className, text) {
        const node = document.createElement(tag);
        if (className) node.className = className;
        if (text) node.textContent = text;
        return node;
    }

    function close() {
        if (!overlay) return;
        overlay.remove();
        overlay = null;
        current = null;
        document.body.classList.remove('confirm-open');
        document.removeEventListener('keydown', onKeyDown, true);
        if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
    }

    function focusables() {
        return Array.from(overlay.querySelectorAll('button, textarea, [href], input, select'))
            .filter(node => !node.disabled && node.offsetParent !== null);
    }

    function onKeyDown(event) {
        if (!overlay) return;

        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }

        if (event.key === 'Tab') {
            const items = focusables();
            if (!items.length) return;
            const first = items[0];
            const last = items[items.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    }

    function open(trigger) {
        if (overlay) close();

        const data = trigger.dataset;
        const hasReason = Boolean(data.reasonField);
        const min = Number(data.reasonMin) || 5;
        const max = Number(data.reasonMax) || 255;
        const neutral = data.confirmTone === 'neutral';

        lastFocus = trigger;
        current = trigger;

        overlay = el('div', 'confirm-overlay');
        const box = el('div', 'confirm-box' + (neutral ? ' is-neutral' : ''));
        box.setAttribute('role', 'alertdialog');
        box.setAttribute('aria-modal', 'true');
        box.setAttribute('aria-labelledby', 'confirm-title');
        box.setAttribute('aria-describedby', 'confirm-message');

        const icon = el('div', 'confirm-icon');
        icon.setAttribute('aria-hidden', 'true');
        const iconName = data.confirmIcon || 'fa-triangle-exclamation';
        icon.innerHTML = '<i class="fa-solid ' + iconName.replace(/[^a-z0-9-]/gi, '') + '"></i>';

        const title = el('h3', 'confirm-title', data.confirmTitle || 'Tem certeza?');
        title.id = 'confirm-title';

        const message = el('p', 'confirm-message', data.confirm || '');
        message.id = 'confirm-message';

        box.append(icon, title);
        if (data.confirm) box.append(message);

        let textarea = null;
        let errorBox = null;
        let reasonWrap = null;

        if (hasReason) {
            reasonWrap = el('div', 'confirm-reason');
            const label = el('label', '', data.reasonLabel || 'Motivo');
            label.htmlFor = 'confirm-reason-input';

            textarea = el('textarea');
            textarea.id = 'confirm-reason-input';
            textarea.maxLength = max;
            textarea.placeholder = data.reasonPlaceholder || '';
            textarea.required = true;

            const foot = el('div', 'confirm-reason-foot');
            errorBox = el('span', 'confirm-error');
            errorBox.setAttribute('role', 'alert');
            const counter = el('span', 'confirm-count', '0/' + max);
            foot.append(errorBox, counter);

            textarea.addEventListener('input', () => {
                counter.textContent = textarea.value.length + '/' + max;
                reasonWrap.classList.remove('has-error');
                errorBox.textContent = '';
            });

            reasonWrap.append(label, textarea, foot);
            box.append(reasonWrap);
        }

        const actions = el('div', 'confirm-actions');
        const cancel = el('button', 'btn btn-transparent btn-sm confirm-cancel', data.confirmCancel || 'Cancelar');
        cancel.type = 'button';
        const ok = el('button', 'btn btn-orange btn-sm confirm-ok' + (neutral ? '' : ' is-danger'), data.confirmOk || 'Confirmar');
        ok.type = 'button';
        actions.append(cancel, ok);
        box.append(actions);

        overlay.append(box);
        document.body.append(overlay);
        document.body.classList.add('confirm-open');
        document.addEventListener('keydown', onKeyDown, true);

        cancel.addEventListener('click', close);
        overlay.addEventListener('mousedown', event => {
            if (event.target === overlay) close();
        });

        ok.addEventListener('click', () => {
            let reason = '';

            if (hasReason) {
                reason = textarea.value.replace(/\s+/g, ' ').trim();

                if (reason.length < min) {
                    reasonWrap.classList.add('has-error');
                    errorBox.textContent = 'Escreva o motivo (mínimo de ' + min + ' caracteres).';
                    textarea.focus();
                    return;
                }
            }

            const trig = current;
            close();
            proceed(trig, reason);
        });

        (hasReason ? textarea : cancel).focus();
    }

    function proceed(trigger, reason) {
        const data = trigger.dataset;

        if (trigger.tagName === 'A') {
            window.location.href = trigger.href;
            return;
        }

        const form = trigger.form || trigger.closest('form');
        if (!form) return;

        if (data.reasonField) {
            let field = form.querySelector('input[type="hidden"][name="' + data.reasonField + '"]');
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = data.reasonField;
                form.append(field);
            }
            field.value = reason;
        }

        // form.submit() não envia o botão que foi clicado: recria o name/value dele
        if (trigger.name) {
            let extra = form.querySelector('input[type="hidden"][data-confirm-extra]');
            if (!extra) {
                extra = document.createElement('input');
                extra.type = 'hidden';
                extra.dataset.confirmExtra = '1';
                form.append(extra);
            }
            extra.name = trigger.name;
            extra.value = trigger.value;
        }

        form.submit();
    }

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-confirm]');
        if (!trigger || trigger.disabled) return;

        event.preventDefault();
        open(trigger);
    });
})();
