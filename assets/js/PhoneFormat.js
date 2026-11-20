const phoneInput = document.getElementById('phone_number');

if (phoneInput) {
    phoneInput.addEventListener('input', function () {
        let value = this.value.replace(/\D/g, '');

        if (value.length > 11) {
            value = value.slice(0, 11);
        }

        if (value.length > 7) {
            value = value.replace(/^(\d{2})(\d)(\d{4})(\d{0,4})$/, '($1) $2 $3-$4');
        } else if (value.length > 3) {
            value = value.replace(/^(\d{2})(\d{0,1})(\d{0,4})$/, '($1) $2 $3');
        } else if (value.length > 0) {
            value = value.replace(/^(\d{0,2})$/, '($1');
        }

        this.value = value;
    });
}
