(function($){
    'use strict';
    const node=document.getElementById('pos-catalog-context');
    if(!node)return;
    window.POS_CURRENCY=node.dataset.currency;
    $(function(){
        function link(boxSelector,subtypeSelector){
            const box=$(boxSelector), subtype=$(subtypeSelector);
            const options=subtype.find('option').toArray().filter(o=>o.value).map(o=>o.cloneNode(true));
            function refresh(){
                const previous=subtype.val(), id=String(box.val()||'');
                subtype.empty().append($('<option value="">Seleccione subtipo...</option>'));
                options.forEach(function(option){
                    const owner=option.getAttribute('data-cash-box-id');
                    if(!owner||owner===id)subtype.append(option.cloneNode(true));
                });
                subtype.val(subtype.find('option').toArray().some(o=>o.value===previous)?previous:'').trigger('change');
            }
            box.on('change',refresh);refresh();
        }
        link('#pv_cash_box_id','#pv_cash_box_subtype_id');
        link('#pv_vuelto_cash_box_id','#pv_vuelto_subtype_id');
    });
})(jQuery);
