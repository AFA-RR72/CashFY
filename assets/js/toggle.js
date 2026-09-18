function toggle() {
    const password = document.getElementById("password");
    const olho = document.getElementById("eye-icon");

    if (password.type === "password") {
        password.type = "text";
        olho.src = "../../uploads/icones/olhoa.png";
        olho.alt = "Ocultar senha";
    } else {
        password.type = "password";
        olho.src = "../../uploads/icones/olhof.png";
        olho.alt = "Mostrar senha";
    }
}