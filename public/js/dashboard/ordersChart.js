(function ($) {
    'use strict';

    var palette = {
        text: '#42536a', muted: '#68798e', grid: 'rgba(104, 121, 142, 0.18)',
        sales: '#2563eb', income: '#15803d', expense: '#b42318'
    };

    function money(value) {
        var number = Number(value);
        return 'S/ ' + (Number.isFinite(number) ? number : 0).toLocaleString('es-PE', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    function rangeLabel(filter, startDate, endDate) {
        if (filter === 'weekly') return 'Últimos 7 días';
        if (filter === 'monthly') return 'Últimos 7 meses';
        if (filter === 'date_range') {
            return new Date(startDate + 'T00:00:00').toLocaleDateString('es-PE') + ' — ' +
                new Date(endDate + 'T00:00:00').toLocaleDateString('es-PE');
        }
        return 'Hoy';
    }

    function options() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 250 },
            legend: {
                position: 'bottom',
                labels: { fontColor: palette.text, usePointStyle: true, boxWidth: 8, padding: 18 }
            },
            tooltips: {
                callbacks: {
                    label: function (item, data) {
                        return data.datasets[item.datasetIndex].label + ': ' + money(item.yLabel);
                    }
                }
            },
            scales: {
                xAxes: [{
                    gridLines: { display: false },
                    ticks: { fontColor: palette.muted, maxRotation: 0, autoSkip: true }
                }],
                yAxes: [{
                    gridLines: { color: palette.grid, zeroLineColor: palette.grid },
                    ticks: {
                        beginAtZero: true,
                        fontColor: palette.muted,
                        callback: function (value) { return 'S/ ' + Number(value).toLocaleString('es-PE'); }
                    }
                }]
            }
        };
    }

    function setState($panel, message) {
        $panel.find('[data-chart-state]').text(message || '').toggleClass('d-none', !message);
        $panel.find('canvas').toggleClass('d-none', Boolean(message));
    }

    function setActive(selector, filter) {
        $(selector).each(function () {
            var active = $(this).data('filter') === filter;
            $(this).toggleClass('active', active).attr('aria-pressed', active ? 'true' : 'false');
        });
    }

    function validRange(startDate, endDate) {
        if (!startDate || !endDate) {
            window.alert('Por favor, seleccione ambas fechas.');
            return false;
        }
        if (startDate > endDate) {
            window.alert('La fecha final no puede ser anterior a la fecha inicial.');
            return false;
        }
        return true;
    }

    $(function () {
        var $salesPanel = $('[data-dashboard-chart="sales"]');
        var $cashflowPanel = $('[data-dashboard-chart="cashflow"]');
        var saleChart = null;
        var cashflowChart = null;

        function loadSales(filter, startDate, endDate) {
            if (!$salesPanel.length) return;
            setState($salesPanel, 'Cargando datos…');
            $.get('/dashboard/sales/chart-data-sale', { filter: filter, start_date: startDate || null, end_date: endDate || null })
                .done(function (data) {
                    var labels = Array.isArray(data.labels) ? data.labels : [];
                    var sales = Array.isArray(data.sales) ? data.sales : [];
                    $('#sale-chart-period').text(rangeLabel(filter, startDate, endDate));
                    $('#quantityKnobTotalSale').text(money(data.total));
                    setActive('.filter-btn-sale', filter);
                    if (!labels.length || !sales.length) {
                        setState($salesPanel, 'No hay ventas para el periodo seleccionado.');
                        if (saleChart) saleChart.destroy();
                        saleChart = null;
                        return;
                    }
                    setState($salesPanel, '');
                    if (saleChart) saleChart.destroy();
                    saleChart = new Chart(document.getElementById('sale-chart').getContext('2d'), {
                        type: 'line',
                        data: { labels: labels, datasets: [{
                            label: 'Ventas', data: sales, fill: false,
                            borderColor: palette.sales, backgroundColor: palette.sales,
                            borderWidth: 2, pointRadius: labels.length === 1 ? 4 : 2,
                            pointHoverRadius: 4, lineTension: 0.18
                        }] },
                        options: options()
                    });
                })
                .fail(function () {
                    setState($salesPanel, 'No fue posible cargar las ventas.');
                    $('#quantityKnobTotalSale').text('No disponible');
                });
        }

        function loadCashflow(filter, startDate, endDate) {
            if (!$cashflowPanel.length) return;
            setState($cashflowPanel, 'Cargando datos…');
            $.get('/dashboard/sales/chart-data-utilidad', { filter: filter, start_date: startDate || null, end_date: endDate || null })
                .done(function (data) {
                    var labels = Array.isArray(data.labels) ? data.labels : [];
                    var incomes = Array.isArray(data.incomes) ? data.incomes : [];
                    var expenses = Array.isArray(data.expenses) ? data.expenses : [];
                    $('#utilidad-chart-period').text(rangeLabel(filter, startDate, endDate));
                    $('#quantityKnobIngresos').text(money(data.total_income));
                    $('#quantityKnobEgresos').text(money(data.total_expense));
                    $('#quantityKnobUtilidad').text(money(data.profit));
                    setActive('.filter-btn-utilidad', filter);
                    if (!labels.length || (!incomes.length && !expenses.length)) {
                        setState($cashflowPanel, 'No hay movimientos para el periodo seleccionado.');
                        if (cashflowChart) cashflowChart.destroy();
                        cashflowChart = null;
                        return;
                    }
                    setState($cashflowPanel, '');
                    if (cashflowChart) cashflowChart.destroy();
                    cashflowChart = new Chart(document.getElementById('utilidad-chart').getContext('2d'), {
                        type: 'line',
                        data: { labels: labels, datasets: [
                            { label: 'Ingresos', data: incomes, fill: false, borderColor: palette.income, backgroundColor: palette.income, borderWidth: 2, pointRadius: labels.length === 1 ? 4 : 2, pointHoverRadius: 4, lineTension: 0.18 },
                            { label: 'Egresos', data: expenses, fill: false, borderColor: palette.expense, backgroundColor: palette.expense, borderWidth: 2, pointRadius: labels.length === 1 ? 4 : 2, pointHoverRadius: 4, lineTension: 0.18 }
                        ] },
                        options: options()
                    });
                })
                .fail(function () {
                    setState($cashflowPanel, 'No fue posible cargar ingresos y egresos.');
                    $('#quantityKnobIngresos, #quantityKnobEgresos, #quantityKnobUtilidad').text('No disponible');
                });
        }

        $('.filter-btn-sale').on('click', function () {
            var filter = $(this).data('filter');
            var startDate = filter === 'date_range' ? $('#start_date_sale').val() : null;
            var endDate = filter === 'date_range' ? $('#end_date_sale').val() : null;
            if (filter === 'date_range' && !validRange(startDate, endDate)) return;
            loadSales(filter, startDate, endDate);
        });

        $('.filter-btn-utilidad').on('click', function () {
            var filter = $(this).data('filter');
            var startDate = filter === 'date_range' ? $('#start_date_utilidad').val() : null;
            var endDate = filter === 'date_range' ? $('#end_date_utilidad').val() : null;
            if (filter === 'date_range' && !validRange(startDate, endDate)) return;
            loadCashflow(filter, startDate, endDate);
        });

        function bindExport(button, modal, start, end) {
            $(button).on('click', function () {
                var startDate = $(start).val();
                var endDate = $(end).val();
                if (!validRange(startDate, endDate)) return;
                window.location.href = $(this).data('url') + '?start_date=' + encodeURIComponent(startDate) + '&end_date=' + encodeURIComponent(endDate);
                $(modal).modal('hide');
            });
        }

        bindExport('#btn-export-range-sales', '#dateRangeModalSale', '#start_date_sale', '#end_date_sale');
        bindExport('#btn-export-cashflow-range', '#dateRangeModalUtilidad', '#start_date_utilidad', '#end_date_utilidad');
        loadSales('daily');
        loadCashflow('daily');
    });
})(jQuery);
