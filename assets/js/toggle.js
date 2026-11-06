function toggle() {
    const password = document.getElementById("password");
    const passConfirm = document.getElementById("pass_confirm");
    const olho = document.getElementById("eye-icon");

    if (password.type === "password") {
        password.type = "text";
        olho.src = "../../uploads/icones/olhoa.png";
        olho.alt = "Ocultar senha";
        passConfirm.type = "text";
    } else {
        password.type = "password";
        olho.src = "../../uploads/icones/olhof.png";
        olho.alt = "Mostrar senha";
        passConfirm.type = "password";
    }
}