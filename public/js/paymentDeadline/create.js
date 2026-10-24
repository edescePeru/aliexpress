function mayus(e) { e.value = e.value.toUpperCase(); }
$(function () {
    var form = document.getElementById('formCreate') || document.getElementById('formEdit');
    if (!form) return;
    var saving = false;
    $(form).on('submit', function (event) {
        event.preventDefault();
        if (saving || !form.reportValidity()) return;
        saving = true;
        var button = $('#btn-submit');
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Guardando');
        $.ajax({url: $(form).data('url'), type: 'POST', data: new FormData(form), processData: false, contentType: false,
            headers: {'Accept': 'application/json'}})
            .done(function (data) {
                var leave = function () { window.location.assign(data.url); };
                if ($.alert) $.alert({title: 'Guardado', content: $('<div>').text(data.message).html(), type: 'green', onClose: leave});
                else { window.alert(data.message); leave(); }
            })
            .fail(function (xhr) {
                var data = xhr.responseJSON || {};
                var messages = data.errors ? Object.keys(data.errors).reduce(function (all, key) { return all.concat(data.errors[key]); }, []) : [data.message || 'No se pudo guardar.'];
                messages.forEach(function (message) { toastr.error(message); });
                saving = false; button.prop('disabled', false).text('Guardar');
            });
    });
});
