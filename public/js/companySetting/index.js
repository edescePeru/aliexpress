$(function () {
    const $form = $('#company-setting-form'), $button = $('#btn-save-settings');
    const originalButtonHtml = $button.html();
    const objectUrls = new Map();
    function escape(value) { return $('<div>').text(String(value || '')).html(); }
    function release(input) {
        if (objectUrls.has(input)) { URL.revokeObjectURL(objectUrls.get(input)); objectUrls.delete(input); }
    }
    function savedPreview($wrapper) {
        const $preview=$wrapper.find('[data-setting-image-preview]'), url=$preview.attr('data-current-src');
        if (url) $preview.attr('src',url).removeClass('d-none');
        else $preview.removeAttr('src').addClass('d-none');
        $wrapper.find('[data-setting-image-status]').text(url?'Imagen actual':'Sin imagen configurada');
    }
    $(document).on('change','[data-setting-image-input]',function () {
        release(this);
        const $wrapper=$(this).closest('[data-setting-image-wrapper]');
        const file=this.files && this.files[0];
        if (!file) { savedPreview($wrapper); return; }
        if (!['image/png','image/jpeg','image/webp'].includes(file.type) || file.size>2097152 || file.size===0) {
            $(this).val('');savedPreview($wrapper);
            $.alert({title:'Imagen inválida',content:'Seleccione PNG, JPG o WebP de hasta 2 MB.',type:'orange'});
            return;
        }
        const url=URL.createObjectURL(file);objectUrls.set(this,url);
        $wrapper.find('[data-setting-image-preview]').attr('src',url).removeClass('d-none');
        $wrapper.find('[data-setting-image-status]').text('Nueva imagen pendiente de guardar');
    });
    $form.on('submit',function(e) {
        e.preventDefault();
        if ($button.prop('disabled')) return;
        // Capture files and the original company before disabling controls.
        const data=new FormData($form[0]);
        const $controls=$form.find(':input:enabled');
        $controls.prop('disabled',true);
        $button.removeClass('btn-primary').addClass('btn-success')
            .html('<i class="fas fa-spinner fa-spin mr-1"></i>Guardando...');
        $.ajax({
            url:$form.attr('action'),method:'POST',data:data,processData:false,contentType:false,
            success:function(response) {
                Object.keys(response.images || {}).forEach(function(id) {
                    const $wrapper=$form.find('[data-setting-image-wrapper="'+id+'"]');
                    const $input=$wrapper.find('[data-setting-image-input]');
                    const url=response.images[id].url || '';
                    if ($input[0])release($input[0]);
                    $input.val('');
                    $wrapper.find('[data-setting-image-preview]').attr('data-current-src',url);
                    savedPreview($wrapper);
                });
                $.alert({title:'Correcto',content:escape(response.message || 'Configuraciones actualizadas correctamente.'),
                    type:'green',buttons:{ok:{text:'ACEPTAR',btnClass:'btn-success'}}});
            },
            error:function(xhr) {
                const response=xhr.responseJSON || {}, messages=[];
                Object.keys(response.errors || {}).forEach(function(key) {
                    messages.push(...response.errors[key]);
                });
                const message=messages.length?messages.map(escape).join('<br>'):
                    escape(response.message || 'No se pudieron guardar las configuraciones.');
                $.alert({title:'Aviso',content:message,type:'orange',buttons:{ok:{text:'ACEPTAR',btnClass:'btn-warning'}}});
            },
            complete:function() {
                $controls.prop('disabled',false);
                $button.removeClass('btn-success').addClass('btn-primary').html(originalButtonHtml);
            }
        });
    });
    $(document).on('change','.custom-control-input[type="checkbox"]',function() {
        const $input=$(this);
        $('label[for="'+$input.attr('id')+'"]').text($input.is(':checked')?'Activado':'Desactivado');
    });
    $(window).on('beforeunload',function() { objectUrls.forEach(url=>URL.revokeObjectURL(url)); });
});
