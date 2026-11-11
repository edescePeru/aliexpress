(function($) {
    'use strict';
    const node=document.getElementById('cash-regularization-context');
    if(!node)return;
    const context={};
    ['tenant','company','branch'].forEach(key=>context['regularization_context_'+key]=node.getAttribute('data-'+key));
    const url=node.getAttribute('data-url'), renderedSession=node.getAttribute('data-session');
    const $form=$('#formRegularize'),$button=$('#btn_regularizar'),original=$button.html();
    let busy=false;
    function escape(value){return $('<div>').text(String(value||'')).html();}
    $(document).on('click','[data-regularizar]',function(e){
        e.preventDefault();if(busy)return;
        const id=$(this).attr('data-id'),amount=Number($(this).attr('data-regularization-amount'));
        $('#cash_movement_id').val(id);
        $('#regularize_amount').val(Number.isFinite(amount)&&amount>0?amount.toFixed(2):'');
        $('#regularize-original-amount').text(Number.isFinite(amount)&&amount>0?'Importe original: '+amount.toFixed(2):'');
        $('#regularize_observation').val('');
        $('#modalRegularize').modal('show');
    });
    $form.on('submit',function(e){
        e.preventDefault();if(busy)return;
        const amount=String($('#regularize_amount').val()||'');
        if(!/^\d{1,12}(?:\.\d{1,2})?$/.test(amount)||Number(amount)<=0){
            $.alert({title:'Validación',content:'Ingrese un monto neto mayor que cero y con hasta dos decimales.',type:'orange'});return;
        }
        const payload=Object.assign({},context,{
            _token:$form.find('input[name="_token"]').val()||$('meta[name="csrf-token"]').attr('content'),
            cash_movement_id:$('#cash_movement_id').val(),amount_regularize:amount,
            observation:$('#regularize_observation').val()
        });
        busy=true;
        const $controls=$form.find(':input:enabled');$controls.prop('disabled',true);
        $button.html('<i class="fas fa-spinner fa-spin mr-1" aria-hidden="true"></i>Regularizando...');
        $.ajax({url:url,method:'POST',data:payload,headers:{Accept:'application/json'},
            success:function(response){
                $('#modalRegularize').modal('hide');
                if(String(response.cash_register_id)===renderedSession&&typeof window.updateBalanceUI==='function')
                    window.updateBalanceUI(response.balance_total);
                $(document).trigger('cash:regularized',[response]);
                $.alert({title:'Regularizado',content:escape(response.message),type:'green'});
            },
            error:function(xhr){
                const body=xhr.responseJSON||{},messages=[];
                Object.keys(body.errors||{}).forEach(key=>messages.push(...body.errors[key]));
                $.alert({title:'No se pudo regularizar',content:messages.length?messages.map(escape).join('<br>'):
                    escape(body.message||'Intente nuevamente.'),type:'orange'});
            },
            complete:function(){busy=false;$controls.prop('disabled',false);$button.html(original);}
        });
    });
})(window.jQuery);
