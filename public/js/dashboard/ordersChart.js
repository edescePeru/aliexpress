$(document).ready(function () {

    $('#dateRangeModal').on('show.bs.modal', function () {
        // Establecer z-index solo cuando el modal se activa
        $(this).css('z-index', 9999); // Fuerza el z-index del modal a ser el más alto
        $('.modal-backdrop').css('z-index', 9998);  // El fondo del modal debe estar debajo del modal
    });

    $('#dateRangeModal').on('hidden.bs.modal', function () {
        // Restablecer el z-index después de cerrar el modal
        $(this).css('z-index', '');  // Restablece el z-index del modal
        $('.modal-backdrop').css('z-index', ''); // Restablece el z-index del fondo del modal
    });

    // Habilitar el drag and drop para las tarjetas
    $('.card').each(function () {
        $(this).find('.card-header').css('cursor', 'move');  // Cambiar el cursor al mover
    });

    // Habilitar drag and drop para los cards
    $('.card').draggable({
        handle: '.card-header', // Se puede mover arrastrando desde el header
        stack: '.card',          // Mantener las tarjetas en una pila
        revert: 'invalid'        // Revertir la tarjeta si no es colocada en un contenedor válido
    });

    $(".knob").knob();

    // Graficos de ventas
    let saleChart;
    let selectedFilterSale = 'daily';

    function fetchChartDataSale(filter, startDate = null, endDate = null) {
        // Actualizar el título según el filtro seleccionado
        updateChartTitleSale(filter, startDate, endDate);

        $.ajax({
            url: '/dashboard/sales/chart-data-sale',
            type: 'GET',
            data: { filter, start_date: startDate, end_date: endDate },
            success: function (response) {
                updateChartSale(response);
                updateKnobsSale(response)
            },
            error: function () {
                alert('Error al obtener los datos del gráfico.');
            }
        });
    }

    function updateChartTitleSale(filter, startDate, endDate) {
        let title = "Total de ventas de hoy"; // Default for 'daily'

        if (filter === 'weekly') {
            title = "Total de ventas de la última semana";
        } else if (filter === 'monthly') {
            title = "Total de ventas de los últimos 7 meses";
        } else if (filter === 'date_range') {
            let start = startDate ? new Date(startDate + "T00:00:00").toLocaleDateString() : '';
            let end = endDate ? new Date(endDate + "T00:00:00").toLocaleDateString() : '';
            title = `Total de ventas desde ${start} hasta ${end}`;
        }

        // Almacenar el título dinámico en una variable global para usarlo en Chart.js
        window.chartTitleSale = title;
    }

    function updateChartSale(data) {
        let ctx = $("#sale-chart").get(0).getContext("2d");

        // Destruir el gráfico anterior si existe
        if (saleChart) {
            saleChart.destroy();
        }

        saleChart = new Chart(ctx, {
            type: 'line',  // Gráfico de línea para visualizar tendencia
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: "Total de ventas (S/.)", // Etiqueta del dataset
                        fill: false,
                        borderColor: "#ffffff", // Color amarillo para resaltar
                        borderWidth: 2,
                        data: data.sales,
                        lineTension: 0.1 // Pequeña curvatura en la línea
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: window.chartTitleSale, // Usar el título dinámico
                    fontSize: 14,
                    fontStyle: 'bold',
                    padding: 3,
                    align: 'center',
                    fontColor: "#ffffff", // Título en blanco
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            fontColor: "#ffffff", // Números del eje X en blanco
                            autoSkip: false,
                            padding: 10
                        },
                        gridLines: {
                            color: "#ffffff", // Líneas del grid en blanco
                            zeroLineColor: "#ffffff"
                        },
                        offset: true
                    }],
                    yAxes: [{
                        ticks: {
                            fontColor: "#ffffff", // Números del eje Y en blanco
                            beginAtZero: true,
                            callback: function(value) {
                                return "S/ " + value.toLocaleString(); // Formato en soles
                            }
                        },
                        gridLines: {
                            color: "#ffffff", // Líneas del grid en blanco
                            zeroLineColor: "#ffffff"
                        }
                    }]
                },
                legend: {
                    labels: {
                        fontColor: "#ffffff" // Color blanco para la leyenda
                    }
                }
            }
        });
    }

    // Cargar datos iniciales (Diario por defecto)
    fetchChartDataSale(selectedFilterSale);

    // Manejo de botones de filtro
    $(".filter-btn-sale").click(function () {
        selectedFilterSale = $(this).data("filter");

        if (selectedFilterSale === "date_range") {
            let startDate = $("#start_date_sale").val();
            let endDate = $("#end_date_sale").val();

            if (!startDate || !endDate) {
                alert("Por favor, seleccione ambas fechas.");
                return;
            }

            fetchChartDataSale(selectedFilterSale, startDate, endDate);
        } else {
            fetchChartDataSale(selectedFilterSale);
        }
    });

    function updateKnobsSale(data) {
        //$('#knobWhatsappSale').val(data.whatsapp_percentage).trigger('change');
        //$('#knobWebSale').val(data.web_percentage).trigger('change');
        //$('#knobTotalSale').val(data.total_percentage).trigger('change');

        //$('#quantityKnobWhatsappSale').text('S/. '+data.total_whatsapp);
        //$('#quantityKnobWebSale').text('S/. '+data.total_web);
        $('#quantityKnobTotalSale').text('S/. '+data.total);
    }

    // Graficos de Ingresos VS Egresos
    let utilidadChart;
    let selectedFilterUtilidad = 'daily';

    function fetchChartDataUtilidad(filter, startDate = null, endDate = null) {
        // Actualizar el título según el filtro seleccionado
        updateChartTitleUtilidad(filter, startDate, endDate);

        $.ajax({
            url: '/dashboard/sales/chart-data-utilidad',
            type: 'GET',
            data: { filter, start_date: startDate, end_date: endDate },
            success: function (response) {
                updateChartUtilidad(response);
                updateKnobsUtilidad(response);
            },
            error: function () {
                alert('Error al obtener los datos del gráfico.');
            }
        });
    }

    function updateChartTitleUtilidad(filter, startDate, endDate) {
        let title = "Ingresos Vs Egresos de hoy"; // Default for 'daily'

        if (filter === 'weekly') {
            title = "Ingresos Vs Egresos de la última semana";
        } else if (filter === 'monthly') {
            title = "Ingresos Vs Egresos de los últimos 7 meses";
        } else if (filter === 'date_range') {
            let start = startDate ? new Date(startDate + "T00:00:00").toLocaleDateString() : '';
            let end = endDate ? new Date(endDate + "T00:00:00").toLocaleDateString() : '';
            title = `Ingresos Vs Egresos desde ${start} hasta ${end}`;
        }

        // Almacenar el título dinámico en una variable global para usarlo en Chart.js
        window.chartTitleUtilidad = title;
    }

    function updateChartUtilidad(data) {
        let ctx = $("#utilidad-chart").get(0).getContext("2d");

        // Destruir el gráfico anterior si existe
        if (utilidadChart) {
            utilidadChart.destroy();
        }

        utilidadChart = new Chart(ctx, {
            type: 'line',  // Gráfico de línea
            data: {
                labels: data.labels,  // Fechas en el eje X
                datasets: [
                    {
                        label: "Ingresos (S/.)",
                        fill: false,
                        borderColor: "#28a745", // Verde para ingresos
                        backgroundColor: "rgba(40, 167, 69, 0.2)", // Sombra verde
                        borderWidth: 2,
                        data: data.incomes, // Datos de ingresos
                        lineTension: 0.1
                    },
                    {
                        label: "Egresos (S/.)",
                        fill: false,
                        borderColor: "#dc3545", // Rojo para egresos
                        backgroundColor: "rgba(220, 53, 69, 0.2)", // Sombra roja
                        borderWidth: 2,
                        data: data.expenses, // Datos de egresos
                        lineTension: 0.1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                title: {
                    display: true,
                    text: window.chartTitleUtilidad, // Usar el título dinámico
                    fontSize: 14,
                    fontStyle: 'bold',
                    padding: 3,
                    align: 'center',
                    fontColor: "#ffffff", // Título en blanco
                },
                scales: {
                    xAxes: [{
                        ticks: {
                            fontColor: "#ffffff",
                            autoSkip: false,
                            padding: 10
                        },
                        gridLines: {
                            color: "rgba(255,255,255,0.2)",
                            zeroLineColor: "#ffffff"
                        }
                    }],
                    yAxes: [{
                        ticks: {
                            fontColor: "#ffffff",
                            beginAtZero: true,
                            callback: function(value) {
                                return "S/ " + value.toLocaleString(); // Formato en soles
                            }
                        },
                        gridLines: {
                            color: "rgba(255,255,255,0.2)",
                            zeroLineColor: "#ffffff"
                        }
                    }]
                },
                legend: {
                    labels: {
                        fontColor: "#ffffff"
                    }
                }
            }
        });
    }

    // Cargar datos iniciales (Diario por defecto)
    fetchChartDataUtilidad(selectedFilterUtilidad);

    // Manejo de botones de filtro
    $(".filter-btn-utilidad").click(function () {
        selectedFilterUtilidad = $(this).data("filter");

        if (selectedFilterUtilidad === "date_range") {
            let startDate = $("#start_date_utilidad").val();
            let endDate = $("#end_date_utilidad").val();

            if (!startDate || !endDate) {
                alert("Por favor, seleccione ambas fechas.");
                return;
            }

            fetchChartDataUtilidad(selectedFilterUtilidad, startDate, endDate);
        } else {
            fetchChartDataUtilidad(selectedFilterUtilidad);
        }
    });

    function updateKnobsUtilidad(data) {
        $('#quantityKnobIngresos').text('S/. '+data.total_income);
        $('#quantityKnobEgresos').text('S/. '+data.total_expense);
        $('#quantityKnobUtilidad').text('S/. '+data.profit);
    }

    $("#btn-pays").on('click', reportPersonalPayment);

    $(document).on('click', '#btn-export-range-sales', function (e) {
        e.preventDefault();

        var $btn = $(this);

        // 🔒 Evitar doble click
        if ($btn.data('busy') === 1) return;

        var startDate = $('#start_date_sale').val();
        var endDate   = $('#end_date_sale').val();

        // Validaciones
        if (!startDate || !endDate) {
            $.alert({
                title: 'Fechas requeridas',
                content: 'Selecciona <b>Fecha Inicio</b> y <b>Fecha Fin</b>.',
                type: 'orange'
            });
            return;
        }

        if (startDate > endDate) {
            $.alert({
                title: 'Rango inválido',
                content: 'La <b>Fecha Fin</b> no puede ser menor que la <b>Fecha Inicio</b>.',
                type: 'red'
            });
            return;
        }

        var prettyStart = startDate.split('-').reverse().join('/');
        var prettyEnd   = endDate.split('-').reverse().join('/');

        $.confirm({
            title: 'Confirmar exportación',
            icon: 'fas fa-file-excel',
            type: 'green',
            theme: 'modern',
            animation: 'zoom',
            closeIcon: true,
            content:
                '¿Deseas exportar las ventas desde <b>' +
                prettyStart +
                '</b> hasta <b>' +
                prettyEnd +
                '</b>?',
            buttons: {
                cancel: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                },
                confirm: {
                    text: 'Sí, exportar',
                    btnClass: 'btn-success',
                    action: function () {

                        // 🔒 Bloquear botón DEFINITIVO
                        $btn.data('busy', 1);

                        $btn.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .html('<i class="fas fa-spinner fa-spin"></i> Exportando...');

                        // Cerrar modal
                        $('#dateRangeModalSale').modal('hide');

                        // Construir URL desde data-url
                        var baseUrl = $btn.data('url');
                        var urlComplete = baseUrl +
                            '?start_date=' + encodeURIComponent(startDate) +
                            '&end_date=' + encodeURIComponent(endDate);

                        // 🚀 Disparar descarga
                        window.location.href = urlComplete;
                    }
                }
            }
        });
    });

    $(document).on('click', '#btn-export-cashflow-range', function (e) {
        e.preventDefault();

        var $btn = $(this);

        // 🔒 Evitar doble click
        if ($btn.data('busy') === 1) return;

        var startDate = $('#start_date_utilidad').val();
        var endDate   = $('#end_date_utilidad').val();

        // Validaciones
        if (!startDate || !endDate) {
            $.alert({
                title: 'Fechas requeridas',
                content: 'Selecciona <b>Fecha Inicio</b> y <b>Fecha Fin</b>.',
                type: 'orange'
            });
            return;
        }

        if (startDate > endDate) {
            $.alert({
                title: 'Rango inválido',
                content: 'La <b>Fecha Fin</b> no puede ser menor que la <b>Fecha Inicio</b>.',
                type: 'red'
            });
            return;
        }

        var prettyStart = startDate.split('-').reverse().join('/');
        var prettyEnd   = endDate.split('-').reverse().join('/');

        $.confirm({
            title: 'Confirmar exportación',
            icon: 'fas fa-file-excel',
            type: 'green',
            theme: 'modern',
            animation: 'zoom',
            closeIcon: true,
            content:
                '¿Deseas exportar las ventas desde <b>' +
                prettyStart +
                '</b> hasta <b>' +
                prettyEnd +
                '</b>?',
            buttons: {
                cancel: {
                    text: 'Cancelar',
                    btnClass: 'btn-secondary'
                },
                confirm: {
                    text: 'Sí, exportar',
                    btnClass: 'btn-success',
                    action: function () {

                        // 🔒 Bloquear botón DEFINITIVO
                        $btn.data('busy', 1);

                        $btn.prop('disabled', true)
                            .removeClass('btn-success')
                            .addClass('btn-secondary')
                            .html('<i class="fas fa-spinner fa-spin"></i> Exportando...');

                        // Cerrar modal
                        $('#dateRangeModalUtilidad').modal('hide');

                        // Construir URL desde data-url
                        var baseUrl = $btn.data('url');
                        var urlComplete = baseUrl +
                            '?start_date=' + encodeURIComponent(startDate) +
                            '&end_date=' + encodeURIComponent(endDate);

                        // 🚀 Disparar descarga
                        window.location.href = urlComplete;
                    }
                }
            }
        });
    });

    $('#dateRangeModalSale').on('show.bs.modal', function () {
        var $btn = $('#btn-export-range-sales');
        $btn.data('busy', 0)
            .prop('disabled', false)
            .removeClass('btn-secondary')
            .addClass('btn-success')
            .html('<i class="fas fa-file-excel"></i> Exportar');
    });

    $('#dateRangeModalUtilidad').on('show.bs.modal', function () {
        var $btn = $('#btn-export-cashflow-range');
        $btn.data('busy', 0)
            .prop('disabled', false)
            .removeClass('btn-secondary')
            .addClass('btn-success')
            .html('<i class="fas fa-file-excel"></i> Exportar');
    });
});

function reportPersonalPayment() {
    // Variables year y month con los valores deseados
    var year = $("#year").val();
    var month = $("#month").val();

    var monthName = $("#month").select2('data')[0].text;

    var params = {
        year: year,
        month: month
    };

    // Realizar la petición $.get para obtener los datos del servidor
    $.get("/dashboard/personal/payments", params, function(response) {
        //console.log(data);
        var data = response.personalPayments;
        var data2 = response.projections;
        var data3 = response.sueldosMensuales;
        var proyectadoDolares = response.projection_dollars; // Valor proyectado en dólares
        var proyectadoSoles = response.projection_soles; // Valor proyectado en soles
        var proyectadoSemanalDolares = response.projection_week_dollars; // Valor proyectado en dólares
        var proyectadoSemanalSoles = response.projection_week_soles; // Valor proyectado en soles
        var currency = response.currency;

        console.log(data);
        console.log(parseFloat(proyectadoDolares).toFixed(2));
        console.log(proyectadoSoles);

        // Llenamos la tabla resumen
        // Construir la tabla dinámica
        var tabla3 = $("<table>").addClass("table table-sm table-bordered table-striped letraTabla");

        var headerRow3 = $("<tr>").addClass("letraTablaGrande");

        // Encabezados de las columnas
        headerRow3.append($("<th>").addClass("titleHeader").addClass("text-center").text("Mes"));

        headerRow3.append($("<th>").addClass("titleTotal").addClass("text-center").text("Total"));
        tabla3.append(headerRow3);

        // Iterar sobre cada trabajador para agregarlos a la tabla
        for (var k = 0; k < data3.length; k++) {
            var trabajador3 = data3[k];
            var row3 = $("<tr>");

            row3.append($("<td>").addClass("celdas").text(trabajador3.nameMonth.toUpperCase()));

            row3.append($("<td>").addClass("totalWorker").addClass("text-right").text(parseFloat(trabajador3.total).toFixed(2)));
            tabla3.append(row3);
        }

        // Agregar la primera fila con los montos en la moneda original
        var primeraFila3 = $("<tr>").addClass("totales");
        if ( currency == 'usd' ) {
            primeraFila3.append($("<td>").addClass("text-right").text("TOTAL EN DOLARES"));
        } else {
            primeraFila3.append($("<td>").addClass("text-right").text("TOTAL EN SOLES"));
        }

        primeraFila3.append($("<td>").addClass("titleTotal").addClass("text-right").text(parseFloat(response.sueldosMensualTotal).toFixed(2)));
        tabla3.append(primeraFila3);

        // Agregar la segunda fila con los montos en dólares
        var segundaFila3 = $("<tr>").addClass("totales");
        if ( currency == 'usd' ) {
            segundaFila3.append($("<td>").addClass("text-right").text("PROMEDIO EN DOLARES"));
        } else {
            segundaFila3.append($("<td>").addClass("text-right").text("PROMEDIO EN SOLES"));
        }

        segundaFila3.append($("<td>").addClass("titleTotal").addClass("text-right").text(parseFloat(response.sueldosMensualPromedio).toFixed(2)));
        tabla3.append(segundaFila3);

        $("#tablaContainer3").append(tabla3);

        // Creamos el grafico
        // Preparar los datos en el formato adecuado para el gráfico de líneas
        var labels = response.sueldosMensuales.map(function(item) {
            return item.shortName;
        });

        var datos = response.sueldosMensuales.map(function(item) {
            return item.total;
        });

        // Configurar y dibujar el gráfico
        var ctx = document.getElementById('lineChart').getContext('2d');
        var lineChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total',
                    data: datos,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

    });

}

function activateTemplate(id) {
    var t = document.querySelector(id);
    return document.importNode(t.content, true);
}