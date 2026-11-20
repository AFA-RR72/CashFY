function toggle() {
    const password = document.getElementById("password");
    const passConfirm = document.getElementById("pass_confirm");
    const olho = document.getElementById("eye-icon");

    if (password.type === "password") {
        password.type = "text";
        olho.classList.add('open-eye')
        olho.alt = "Ocultar senha";
        passConfirm.type = "text";
    } else {
        password.type = "password";
        olho.classList.remove('open-eye')
        olho.alt = "Mostrar senha";
        passConfirm.type = "password";
    }
}