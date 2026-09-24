document.addEventListener("DOMContentLoaded", () => {
    const cookieBanner = document.getElementById("cookie-banner");
    const acceptButton = document.getElementById("cookie-accept");
    const rejectButton = document.getElementById("cookie-reject");

    const cookieChoice = localStorage.getItem("cookieChoice");

    // Se já escolheu, não mostra o banner
    if (cookieChoice === "accepted" || cookieChoice === "rejected") {
        cookieBanner.style.display = "none";
        return;
    }

    // Primeira vez
    cookieBanner.style.display = "block";

    // Aceitar
    acceptButton.addEventListener("click", () => {
        localStorage.setItem("cookieChoice", "accepted");
        cookieBanner.style.display = "none";
    });

    // Recusar
    rejectButton.addEventListener("click", () => {
        localStorage.setItem("cookieChoice", "rejected");
        window.location.href = "https://www.google.com";
    });
});
