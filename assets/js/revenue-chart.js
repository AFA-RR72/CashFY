/*
 * Gráfico de faturamento (painel do vendedor — aba Rendimento)
 * Requer Chart.js carregado antes deste arquivo.
 *
 * Markup esperado (ver perfil.php):
 *   #revenue-chart-card[data-endpoint]  .chart-mode-btn[data-mode]
 *   #chart-product-filter > #chart-product (select)
 *   #chart-summary  #chart-message  #chart-wrap > canvas#revenue-chart
 */
(function () {
    'use strict';

    const card = document.getElementById('revenue-chart-card');
    if (!card) return;

    const canvas = document.getElementById('revenue-chart');
    const wrap = document.getElementById('chart-wrap');
    const message = document.getElementById('chart-message');
    const summary = document.getElementById('chart-summary');
    const filter = document.getElementById('chart-product-filter');
    const select = document.getElementById('chart-product');
    const monthSelect = document.getElementById('chart-month');
    const modeButtons = card.querySelectorAll('.chart-mode-btn');
    const endpoint = card.dataset.endpoint;

    const MONTHS = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    const brl = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });

    let mode = 'month';
    let chart = null;
    let controller = null;
    let granularity = 'month'; // 'month' (histórico) ou 'day' (dias de um mês)
    let rawLabels = [];

    function showMessage(text, isError) {
        message.textContent = text;
        message.classList.toggle('error', Boolean(isError));
        message.hidden = false;
        wrap.hidden = true;
        summary.textContent = '';
    }

    function hideMessage() {
        message.hidden = true;
        wrap.hidden = false;
    }

    if (typeof Chart === 'undefined') {
        showMessage('Não foi possível carregar a biblioteca de gráficos. Verifique sua conexão e recarregue a página.', true);
        return;
    }

    // "2026-09" -> "set/26"
    function monthLabel(ym) {
        const [year, month] = ym.split('-');
        return MONTHS[Number(month) - 1] + '/' + year.slice(2);
    }

    // Cores vêm das variáveis CSS do projeto (acompanham o dark mode)
    function themeColors() {
        const css = getComputedStyle(document.body);
        const read = (name) => css.getPropertyValue(name).trim();

        return {
            teal: read('--teal-700'),
            orange: read('--orange-500'),
            text: read('--ink-soft'),
            grid: read('--line'),
            font: read('--font-body') || 'sans-serif'
        };
    }

    function applyTheme() {
        if (!chart) return;

        const colors = themeColors();
        const barColor = mode === 'product' ? colors.orange : colors.teal;

        chart.data.datasets[0].backgroundColor = barColor;
        chart.options.scales.x.ticks.color = colors.text;
        chart.options.scales.y.ticks.color = colors.text;
        chart.options.scales.y.grid.color = colors.grid;
        chart.options.scales.x.border.color = colors.grid;
        chart.update('none');
    }

    function render(data) {
        const colors = themeColors();
        const barColor = data.mode === 'product' ? colors.orange : colors.teal;
        const datasetLabel = data.product ? data.product.name : 'Faturamento';
        granularity = data.granularity;
        rawLabels = data.labels;
        // dia: "05"; mês: "set/26"
        const labels = data.labels.map((key) => granularity === 'day' ? String(Number(key.slice(8))) : monthLabel(key));

        if (!chart) {
            Chart.defaults.font.family = colors.font;
            Chart.defaults.font.weight = '600';

            chart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: datasetLabel,
                        data: data.values,
                        backgroundColor: barColor,
                        borderRadius: 8,
                        borderSkipped: false,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => {
                                    const key = rawLabels[items[0].dataIndex];
                                    return granularity === 'day'
                                        ? key.slice(8) + '/' + key.slice(5, 7) + '/' + key.slice(0, 4)
                                        : monthLabel(key);
                                },
                                label: (ctx) => ' ' + brl.format(ctx.parsed.y)
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { color: colors.grid },
                            ticks: { color: colors.text }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: colors.grid },
                            border: { display: false },
                            ticks: {
                                color: colors.text,
                                callback: (value) => 'R$ ' + Number(value).toLocaleString('pt-BR')
                            }
                        }
                    }
                }
            });
        } else {
            chart.data.labels = labels;
            chart.data.datasets[0].label = datasetLabel;
            chart.data.datasets[0].data = data.values;
            chart.data.datasets[0].backgroundColor = barColor;
            chart.update();
        }

        const prefix = data.product ? data.product.name + ' — total' : 'Total';
        const scope = granularity === 'day' ? ' em ' + monthLabel(data.month) : ' no período';
        summary.textContent = prefix + scope + ': ' + brl.format(data.total);
    }

    async function load() {
        // Cancela a requisição anterior: evita que uma resposta antiga sobrescreva a mais recente
        if (controller) {
            controller.abort();
            controller = null;
        }
        wrap.classList.remove('is-loading');

        const productId = select ? select.value : '';

        if (mode === 'product' && !productId) {
            if (select && select.options.length <= 1) {
                showMessage('Você ainda não tem produtos cadastrados.', false);
            } else {
                showMessage('Selecione um produto para ver o faturamento mensal dele.', false);
            }
            return;
        }

        const params = new URLSearchParams({ mode: mode, month: monthSelect ? monthSelect.value : 'all' });
        if (mode === 'product') params.set('product_id', productId);

        const current = new AbortController();
        controller = current;

        wrap.classList.add('is-loading');

        try {
            const response = await fetch(endpoint + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                signal: current.signal
            });

            const data = await response.json().catch(() => null);

            if (!response.ok || !data || !data.ok) {
                throw new Error((data && data.error) || 'Não foi possível carregar o gráfico.');
            }

            if (data.labels.length === 0) {
                showMessage('Você ainda não registrou vendas. Use o Diário para começar.', false);
                return;
            }

            hideMessage();
            render(data);
        } catch (error) {
            if (error.name === 'AbortError') return;
            showMessage(error.message || 'Erro ao carregar o gráfico.', true);
        } finally {
            if (controller === current) {
                wrap.classList.remove('is-loading');
            }
        }
    }

    function setMode(newMode) {
        mode = newMode;

        modeButtons.forEach((button) => {
            const active = button.dataset.mode === newMode;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });

        // O seletor de produtos só aparece no modo "Por produto"
        filter.hidden = newMode !== 'product';

        load();
    }

    modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.dataset.mode !== mode) setMode(button.dataset.mode);
        });
    });

    if (select) {
        select.addEventListener('change', load);
    }

    if (monthSelect) {
        monthSelect.addEventListener('change', load);
    }

    // Reaplica as cores quando o usuário troca entre tema claro/escuro
    new MutationObserver(applyTheme).observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    load();
})();
