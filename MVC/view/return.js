function voltarPagina(event) {
    event.preventDefault();

    if (window.location.hash) {
        history.go(-2);
    } else {
        history.go(-1);
    }
}