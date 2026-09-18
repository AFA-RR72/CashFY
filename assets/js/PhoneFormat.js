const phoneInput = document.getElementById('phone_number');

phoneInput.addEventListener('input', function () {
    let value = this.value.replace(/\D/g, '');

    if (value.length > 11) {
        value = value.slice(0, 11);
    }

    if (value.length > 7) {
        value = value.replace(
            /^(\d{2})(\d)(\d{4})(\d{0,4})$/,
            '($1) $2 $3-$4'
        );
    } else if (value.length > 3) {
        value = value.replace(
            /^(\d{2})(\d{0,1})(\d{0,4})$/,
            '($1) $2 $3'
        );
    } else if (value.length > 0) {
        value = value.replace(
            /^(\d{0,2})$/,
            '($1'
        );
    }

    this.value = value;
    function formatPhoneNumber(phoneNumber) {
        const number = phoneNumber.replace(/\D/g, '');

        if (number.length === 11) {
            return `(${number.substring(0, 2)}) ${number.substring(2, 7)}-${number.substring(7, 11)}`;
        }

        if (number.length === 10) {
            return `(${number.substring(0, 2)}) ${number.substring(2, 6)}-${number.substring(6, 10)}`;
        }

        return number;
    }

});

function formatPhoneNumber(phoneNumber) {
        const number = phoneNumber.replace(/\D/g, '');

        if (number.length === 11) {
            return `(${number.substring(0, 2)}) ${number.substring(2, 7)}-${number.substring(7, 11)}`;
        }

        if (number.length === 10) {
            return `(${number.substring(0, 2)}) ${number.substring(2, 6)}-${number.substring(6, 10)}`;
        }

        return number;
    }