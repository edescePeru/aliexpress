/* Dashboard export only. Do not load reportAmount.js: its rotation/legacy requests are unrelated. */
(function ($) {
    'use strict';
    $(function () {
        const form = document.getElementById('dashboard-entry-export');
        if (!form) return;
        let busy = false;
        const $error = $('#dashboard-entry-export-error'), $button = $('#btn-submitExport'), $status = $('#dashboard-entry-export-status');
        $('.dashboard-entry-date', form).datepicker({format: 'dd/mm/yyyy', language: 'es', autoclose: true, clearBtn: true, todayBtn: 'linked', orientation: 'bottom auto', container: '.wrapper'});

        function dateValue(value) {
            const match = /^(\d{2})\/(\d{2})\/(\d{4})$/.exec(value);
            if (!match) return null;
            const d = new Date(Number(match[3]), Number(match[2]) - 1, Number(match[1]));
            return d.getFullYear() === Number(match[3]) && d.getMonth() === Number(match[2]) - 1 && d.getDate() === Number(match[1]) ? d.getTime() : null;
        }
        async function download(query) {
            $status.text('Preparando archivo…');
            try {
                const response = await fetch(form.action + '?' + query.toString(), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const type = response.headers.get('content-type') || '';
                if (!response.ok || /json|text\/html/i.test(type)) {
                    let message = 'No se pudo descargar el reporte. Comprueba tu sesión y permisos.';
                    if (/json/i.test(type)) {
                        const data = await response.json(), errors = data.errors && Object.values(data.errors);
                        message = errors && errors.length ? [].concat(errors[0])[0] : data.message || message;
                    }
                    throw new Error(message);
                }
                const blob = await response.blob(), url = URL.createObjectURL(blob), link = document.createElement('a');
                link.href = url; link.download = 'reporteEntradasCompraInventario.xlsx';
                document.body.appendChild(link); link.click(); link.remove();
                window.setTimeout(function () { URL.revokeObjectURL(url); }, 1000);
                $status.text('Archivo preparado.');
            } catch (error) {
                $status.empty(); $error.removeClass('d-none').text(error.message || 'No se pudo descargar el reporte.');
            } finally {
                busy = false; $button.prop('disabled', false); form.removeAttribute('aria-busy');
            }
        }
        $(form).on('submit.dashboardEntryExport', function (event) {
            event.preventDefault();
            if (busy) return;
            $error.addClass('d-none').empty(); $status.empty();
            const start = form.elements.start.value.trim(), end = form.elements.end.value.trim();
            if ((start || end) && (!start || !end || dateValue(start) === null || dateValue(end) === null || dateValue(start) > dateValue(end))) {
                $error.removeClass('d-none').text('Indica ambas fechas válidas en formato dd/mm/aaaa; la fecha final no puede ser anterior a la inicial.');
                return;
            }
            const query = new URLSearchParams({typeEntry: form.elements.typeEntry.value, start: start, end: end});
            busy = true; $button.prop('disabled', true); form.setAttribute('aria-busy', 'true');
            if (!start && !end) {
                $.confirm({title: 'Exportar todas las fechas', content: 'Se incluirán las entradas del tipo seleccionado de toda la empresa activa. ¿Continuar?', buttons: {
                    confirm: {text: 'Descargar', action: function () { download(query); }},
                    cancel: {text: 'Cancelar', action: function () { busy = false; $button.prop('disabled', false); form.removeAttribute('aria-busy'); }}
                }});
            } else download(query);
        });
    });
})(window.jQuery);
