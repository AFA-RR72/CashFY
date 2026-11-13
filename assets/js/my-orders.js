/*
 * Meus pedidos: filtra os cartões por status sem recarregar a página.
 * O filtro escolhido fica no hash da URL (ex.: myOrders.php#recusados), então dá para
 * compartilhar/voltar para ele. Sem JavaScript, todos os pedidos aparecem.
 */
(function () {
    const buttons = Array.from(document.querySelectorAll('.mo-filter'));
    const cards = Array.from(document.querySelectorAll('.mo-card'));
    const emptyBox = document.getElementById('mo-filter-empty');

    if (!buttons.length) return;

    const hashes = { all: '', progress: 'em-andamento', completed: 'concluidos', rejected: 'recusados' };
    const byHash = Object.fromEntries(Object.entries(hashes).map(([key, value]) => [value, key]));

    function apply(filter) {
        let visible = 0;

        cards.forEach(card => {
            const show = filter === 'all' || card.dataset.group === filter;
            card.hidden = !show;
            if (show) visible++;
        });

        buttons.forEach(button => {
            const active = button.dataset.filter === filter;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        if (emptyBox) emptyBox.hidden = visible !== 0;
    }

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            const filter = button.dataset.filter;
            apply(filter);

            const hash = hashes[filter];
            history.replaceState(null, '', hash ? '#' + hash : location.pathname + location.search);
        });
    });

    const fromHash = byHash[location.hash.replace('#', '')];
    apply(fromHash || 'all');
})();
