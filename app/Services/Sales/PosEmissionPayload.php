<?php
namespace App\Services\Sales;
use App\Sale;
use App\Services\NubefactCompanyConnection;

class PosEmissionPayload
{
    // Pure builder. Catalog/tax snapshots were persisted inside the sale transaction.
    // Provider affectation codes: user-supplied NUBEFACT DOC API JSON V1, tipo_de_igv table.
    public function build(Sale $sale,NubefactCompanyConnection $connection): array
    {
        if(!in_array($sale->type_document,['01','03'],true))throw new \RuntimeException('Tipo de comprobante inválido.');
        $sale->loadMissing('details');$items=[];$rates=[];
        foreach($sale->details as$detail){
            $qty=$detail->packs!==null?$detail->packs:$detail->quantity;
            if(bccomp((string)$qty,'0',4)<=0||!in_array((int)$detail->tax_provider_code,[1,8,9],true))throw new \RuntimeException('El detalle no tiene una identidad fiscal válida.');
            $base=bcsub((string)$detail->total,(string)$detail->tax_amount,2);
            $description=$detail->description?:'Producto';
            if($detail->packs!==null)$description.=' | Presentación: '.($detail->presentation_label_snapshot?:'Paquete').' ('.$detail->units_per_pack.' unidades)';
            $codes=collect($detail->item_snapshot?:[])->pluck('code')->filter()->implode(', ');
            if($codes!=='')$description.=' | Items: '.$codes;
            $items[]=['unidad_de_medida'=>'NIU','codigo'=>(string)$detail->stock_item_id,'descripcion'=>$description,'cantidad'=>(float)$qty,
                'valor_unitario'=>(float)bcdiv($base,(string)$qty,10),'precio_unitario'=>(float)$detail->price,
                'subtotal'=>(float)$base,'tipo_de_igv'=>(int)$detail->tax_provider_code,'igv'=>(float)$detail->tax_amount,'total'=>(float)$detail->total];
            if((int)$detail->tax_provider_code===1)$rates[(string)(float)($detail->tax_rate_snapshot??$detail->percentage_tax)]=true;
        }
        if(count($rates)>1)throw new \RuntimeException('El proveedor requiere un solo porcentaje de IGV por comprobante; revise las tasas antes de emitir.');
        return ['operacion'=>'generar_comprobante','tipo_de_comprobante'=>$sale->type_document==='01'?1:2,
            'serie'=>$connection->saleSeries($sale->type_document),'numero'=>'','sunat_transaction'=>1,
            'cliente_tipo_de_documento'=>$sale->tipo_documento_cliente,'cliente_numero_de_documento'=>$sale->numero_documento_cliente,
            'cliente_denominacion'=>$sale->nombre_cliente,'cliente_direccion'=>$sale->direccion_cliente?:'','cliente_email'=>$sale->email_cliente?:'',
            'fecha_de_emision'=>$sale->date_sale->format('d-m-Y'),'moneda'=>$sale->currency==='PEN'?1:2,
            'porcentaje_de_igv'=>$rates?(float)array_keys($rates)[0]:0,
            'total_gravada'=>(float)$sale->op_gravada,'total_exonerada'=>(float)$sale->op_exonerada,'total_inafecta'=>(float)$sale->op_inafecta,
            'total_igv'=>(float)$sale->igv,'total'=>(float)$sale->importe_total,'total_a_pagar'=>(float)$sale->importe_total,
            'enviar_automaticamente_a_la_sunat'=>true,'enviar_automaticamente_al_cliente'=>false,'items'=>$items];
    }
}
