(function ($) {
    'use strict';
    $(function () {
        var root = $('.tenant-context-navbar');
        if (!root.length) return;
        var company = $('#tenant-context-company'), branch = $('#tenant-context-branch');
        var request = null, loading = false, saving = false;
        function message(xhr, fallback) {
            var data = xhr.responseJSON || {}, errors = data.errors || {}, keys = Object.keys(errors);
            return keys.length ? errors[keys[0]][0] : (data.message || fallback);
        }
        function reset(text) { branch.empty().append($('<option>', {value: '', text: text})).prop('disabled', true); }
        company.on('change', function () {
            if (request) request.abort();
            var id = company.val();
            reset(id ? 'Cargando locales...' : 'Seleccione un local');
            loading = false;
            if (!id) return;
            loading = true;
            request = $.ajax({url: root.attr('data-branches-url'), method: 'GET', data: {company_id: id},
                success: function (data) {
                    if (company.val() !== id) return;
                    reset('Seleccione un local');
                    $.each(data.branches || [], function (_, row) { branch.append($('<option>', {value: row.id, text: row.name})); });
                    branch.prop('disabled', !(data.branches && data.branches.length));
                    if (!(data.branches && data.branches.length)) reset('Sin locales autorizados');
                },
                error: function (xhr, status) { if (status !== 'abort') toastr.error(message(xhr, 'No se pudieron cargar los locales.')); },
                complete: function () { if (company.val() === id) loading = false; }
            });
        });
        function post(button, url, values) {
            if (saving) return;
            saving = true;
            var original = button.html();
            button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Procesando...');
            company.prop('disabled', true); branch.prop('disabled', true);
            values._token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({url: url, method: 'POST', data: values,
                success: function () { window.location.assign(root.attr('data-home-url')); },
                error: function (xhr) { toastr.error(message(xhr, 'No se pudo actualizar el contexto.')); saving = false; button.prop('disabled', false).html(original); company.prop('disabled', false); branch.prop('disabled', !company.val() || branch.find('option').length < 2); }
            });
        }
        $('#tenant-context-change').on('click', function (event) {
            event.preventDefault(); event.stopPropagation();
            if (loading) { toastr.info('Espera a que se carguen los locales.'); return; }
            if (!company.val() || !branch.val()) { toastr.warning('Selecciona una empresa y un local.'); return; }
            post($(this), root.attr('data-update-url'), {company_id: company.val(), branch_id: branch.val()});
        });
        $('#tenant-context-clear').on('click', function (event) { event.preventDefault(); event.stopPropagation(); post($(this), root.attr('data-clear-url'), {}); });
        $('[data-owner-company]').on('click', function () {
            company.val(String($(this).attr('data-owner-company'))).trigger('change');
            root.find('[data-toggle="dropdown"]').dropdown('show');
            company.trigger('focus');
        });
    });
})(jQuery);
