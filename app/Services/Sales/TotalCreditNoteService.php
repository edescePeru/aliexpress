<?php
namespace App\Services\Sales;

use App\{Sale,CreditNote,CreditNoteDetail};
use App\Support\TenantContext;
use App\Services\NubefactCompanyConfigService;
use Illuminate\Support\Facades\{DB,Http};

/** Full credit: durable fiscal request plus immediate, idempotent operational reversal. */
class TotalCreditNoteService
{
    private function access($id): Sale
    {
        app(SaleListAccess::class)->permission('annul_puntoVenta');
        return app(SaleListAccess::class)->sale($id);
    }
    private function operations($id)
    {
        return DB::table('sale_total_credit_operations')->where('tenant_id',TenantContext::tenantId())
            ->where('company_id',TenantContext::companyId())->where('branch_id',TenantContext::branchId())->where('sale_id',$id);
    }
    private function note(Sale $sale)
    {
        return $sale->creditNotes()->where('credit_note_type','total')->latest('id')->first();
    }
    public function flags(Sale $sale): array
    {
        $note=$this->note($sale);if(!$note)return ['id'=>null,'can_consult'=>false];
        $op=$this->operations($sale->id)->first();
        return ['id'=>$note->id,'status'=>$op&&((int)$op->credit_note_id===(int)$note->id)?$op->status:$note->status,
            'serie'=>$note->serie,'numero'=>$note->numero,'can_consult'=>!(bool)$sale->free_sale];
    }
    public function preview($id): array
    {
        $sale=$this->access($id);$note=$this->note($sale);$this->eligible($sale,!$note);$op=$this->operations($id)->first();
        if(!$note)$this->payload($sale,new CreditNote(['generation_key'=>'preview']),app(NubefactCompanyConfigService::class)->forSale($sale));
        $financial=app(SaleAnnulmentReversal::class)->preview($sale);
        return array_merge($financial,['sale_id'=>$sale->id,'code'=>'VENTA - '.$sale->id,'currency'=>$sale->currency,
            'original_total'=>number_format((float)$sale->importe_total,2,'.',''),'original_document'=>($sale->type_document==='01'?'Factura ':'Boleta ').$sale->serie_sunat.'-'.$sale->numero,'credit_note_id'=>$note?$note->id:null,
            'total_request_key'=>$op?$op->request_key:bin2hex(random_bytes(16)),
            'credit_note_status'=>$op?$op->status:($note?$note->status:null)]);
    }
    private function eligible(Sale $sale,bool $new): void
    {
        if($sale->free_sale||$sale->requiresEmissionReview()||!$sale->hasElectronicDocument()||!$sale->serie_sunat||!$sale->numero){
            throw new \DomainException('La venta debe tener un comprobante confirmado y pertenecer al flujo de ventas habilitado.');
        }
        if($sale->creditNotes()->where('credit_note_type','partial')->exists()){
            throw new \DomainException('La venta tiene notas parciales. Devuelva las cantidades restantes mediante ese flujo.');
        }
        if($new&&$sale->state_annulled)throw new \DomainException('La venta ya está anulada. Revise sus documentos existentes.');
        if($new&&$sale->annulment_type!=='credit_note'&&in_array($sale->annulment_status,['pending','waiting_sunat_process','unknown'],true)){
            throw new \DomainException('Primero concilie la baja pendiente del comprobante; no se generará una NC en paralelo.');
        }
    }
    private function payload(Sale $sale,CreditNote $note,$connection): array
    {
        $payload=app(ExistingSaleInvoice::class)->payload($sale,$connection);
        $payload['tipo_de_comprobante']=3;$payload['serie']=$connection->creditNoteSeries($sale->type_document);
        $payload['numero']='';$payload['codigo_unico']=$note->generation_key;
        $payload['tipo_de_nota_de_credito']='01';$payload['motivo_o_sustento_de_nota_de_credito']='Anulación de la operación';
        $payload['documento_que_se_modifica_tipo']=$sale->type_document==='01'?1:2;
        $payload['documento_que_se_modifica_serie']=$sale->serie_sunat;$payload['documento_que_se_modifica_numero']=(string)$sale->numero;
        return $payload;
    }
    private function reverse(Sale $sale,CreditNote $note,array $data): ?int
    {
        $q=DB::table('sale_annulment_operations')->where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id)
            ->where('branch_id',$sale->branch_id)->where('sale_id',$sale->id);
        $operation=$q->lockForUpdate()->first();$already=$sale->internal_reversal_status==='reversed';
        if(!$already&&($note->internal_reversal_status==='reversed'||$note->cash_refund_status==='refunded'||$note->cash_movement_id))throw new \DomainException('La nota registra una devolución histórica que necesita conciliación; no se repetirá.');
        if(!$operation&&!$already){
            $id=DB::table('sale_annulment_operations')->insertGetId(['tenant_id'=>$sale->tenant_id,'company_id'=>$sale->company_id,
                'branch_id'=>$sale->branch_id,'sale_id'=>$sale->id,'requested_by'=>auth()->id(),'reference'=>bin2hex(random_bytes(10)),
                'status'=>'credit_note','reversal_state'=>'none','credit_note_id'=>$note->id,'created_at'=>now(),'updated_at'=>now()]);
            $operation=$q->where('id',$id)->first();
        }
        if(!$already){
            if(!$operation)throw new \DomainException('Falta la operación que respalda el reingreso.');
            if($operation->reversal_state!=='none')throw new \DomainException('La reversión necesita conciliarse antes de registrar otra devolución.');
            $q->update(['credit_note_id'=>$note->id]);
            app(SaleAnnulmentReversal::class)->apply($sale,$operation,$data);$operation=DB::table('sale_annulment_operations')->where('id',$operation->id)->where('tenant_id',$sale->tenant_id)->where('company_id',$sale->company_id)->where('branch_id',$sale->branch_id)->first();
            $note->debt_reduction_amount=$operation->debt_cancelled;$note->cash_refund_amount=$operation->refund_amount;
            $note->cash_movement_id=$operation->refund_movement_id;$note->refund_cash_box_id=$operation->refund_cash_box_id;
            $note->refund_subtype_id=$operation->refund_subtype_id;$note->cash_refund_status=(float)$operation->refund_amount>0?'refunded':'none';
            if($note->cash_refund_status==='refunded'){$note->cash_refund_at=now();$note->cash_refund_by=auth()->id();}
            $note->settlement_version=2;
        }
        // If an earlier annulment already returned stock/money, the NC only regularizes the fiscal document.
        $note->internal_reversal_status='reversed';$note->internal_reversed_at=$note->internal_reversed_at?:($sale->internal_reversed_at?:now());
        $note->internal_reversed_by=$note->internal_reversed_by?:auth()->id();$note->save();
        $sale->annulment_type='credit_note';$sale->credit_note_status='total';$sale->annulment_status='pending';$sale->save();
        return $operation?$operation->id:null;
    }
    public function execute($id,array $data,bool $consult=false,?callable $files=null): array
    {
        $this->access($id);if(DB::transactionLevel()!==0)throw new \LogicException('La NC debe comenzar fuera de otra transacción.');
        $claim=DB::transaction(function()use($id,$data,$consult){
            $sale=Sale::where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())
                ->where('branch_id',TenantContext::branchId())->whereKey($id)->lockForUpdate()->firstOrFail();$this->access($id);
            $note=$this->note($sale);$op=$this->operations($id)->lockForUpdate()->first();$this->eligible($sale,!$note);
            if($op){
                if(!$note||(int)$op->credit_note_id!==(int)$note->id)throw new \DomainException('La operación no coincide con la nota de crédito.');
                if(!$consult){
                    if(!empty($data['financial_token'])&&$op->request_hash&&!hash_equals($op->request_hash,$this->fingerprint($data)))throw new \DomainException('La NC ya está registrada. No se cambiará su canal ni se realizará otro envío; consulte la misma nota.');
                    return ['blocked'=>true,'sale_id'=>$id];
                }
                $saved=DB::table('sale_total_credit_attempts')->where('operation_id',$op->id)->latest('id')->first();
                if($op->status==='processing'&&$saved&&in_array($saved->status,['accepted','pending','rejected'],true)){
                    return ['blocked'=>false,'local_reply'=>true,'operation_id'=>$op->id,'sale_id'=>$id,'note_id'=>$note->id,
                        'result'=>json_decode($saved->response_payload,true),'status'=>$saved->status,'payload'=>json_decode($saved->request_payload,true)];
                }
                if($op->status==='processing'&&\Carbon\Carbon::parse($op->updated_at)->gt(now()->subSeconds(90)))return ['blocked'=>true,'sale_id'=>$id];
                if($op->status==='accepted')return ['blocked'=>true,'sale_id'=>$id];
            }
            if(!$note){
                if($consult)throw new \DomainException('Esta venta no tiene una nota de crédito total para consultar.');
                if(empty($data['total_request_key']))throw new \DomainException('Abra la confirmación de la nota de crédito para continuar.');
                $note=CreditNote::create(['sale_id'=>$sale->id,'type_document'=>'07','credit_note_type'=>'total','status'=>'pending',
                    'reason_code'=>'01','reason_description'=>'Anulación de la operación','generation_key'=>bin2hex(random_bytes(10)),
                    'op_gravada'=>$sale->op_gravada,'op_exonerada'=>$sale->op_exonerada,'op_inafecta'=>$sale->op_inafecta,
                    'igv'=>$sale->igv,'total_descuentos'=>$sale->total_descuentos,'importe_total'=>$sale->importe_total,'created_by'=>auth()->id()]);
                $connection=app(NubefactCompanyConfigService::class)->forSale($sale);$payload=$this->payload($sale,$note,$connection);
                foreach($sale->details as$d){
                    $base=bcsub((string)$d->total,(string)$d->tax_amount,2);
                    CreditNoteDetail::create(['credit_note_id'=>$note->id,'sale_detail_id'=>$d->id,'description'=>$d->description,
                        'quantity'=>$d->quantity,'price'=>bcdiv((string)$d->total,(string)$d->quantity,10),
                        'valor_unitario'=>bcdiv($base,(string)$d->quantity,10),'subtotal'=>$base,'igv'=>$d->tax_amount,'total'=>$d->total]);
                }
                $reversalId=$this->reverse($sale,$note,$data);
                $opId=DB::table('sale_total_credit_operations')->insertGetId(['tenant_id'=>$sale->tenant_id,'company_id'=>$sale->company_id,
                    'branch_id'=>$sale->branch_id,'sale_id'=>$sale->id,'credit_note_id'=>$note->id,'requested_by'=>auth()->id(),
                    'reference'=>$note->generation_key,'request_key'=>$data['total_request_key'],'connection_fingerprint'=>$this->connectionFingerprint($connection),'request_hash'=>$this->fingerprint($data),
                    'status'=>'processing','generation_payload'=>json_encode($payload),'financial_data'=>json_encode($data),
                    'reversal_operation_id'=>$reversalId,'created_at'=>now(),'updated_at'=>now()]);
                $op=$this->operations($id)->where('id',$opId)->first();
            }else{
                if(!$consult)return ['blocked'=>true,'sale_id'=>$id,'legacy'=>true];
                if(!$op){
                    // Adopt a legacy note for CONSULTATION ONLY; never regenerate it or replace its key.
                    $reversalId=$this->reverse($sale,$note,$data);
                    $opId=DB::table('sale_total_credit_operations')->insertGetId(['tenant_id'=>$sale->tenant_id,'company_id'=>$sale->company_id,
                        'branch_id'=>$sale->branch_id,'sale_id'=>$sale->id,'credit_note_id'=>$note->id,'requested_by'=>auth()->id(),
                        'reference'=>$note->generation_key?:('legacy-'.$note->id),'request_key'=>'legacy-total-'.$note->id,'status'=>$note->status,
                        'reversal_operation_id'=>$reversalId,'created_at'=>now(),'updated_at'=>now()]);$op=$this->operations($id)->where('id',$opId)->first();
                }
                if(!$note->serie||!$note->numero){$this->operations($id)->update(['status'=>'unknown','updated_at'=>now()]);return ['blocked'=>true,'sale_id'=>$id];}
                $connection=app(NubefactCompanyConfigService::class)->forSale($sale);
                if($op->connection_fingerprint&&!hash_equals($op->connection_fingerprint,$this->connectionFingerprint($connection)))throw new \DomainException('La conexión emisora cambió. Revise la configuración original antes de consultar esta nota; no se realizará otro envío.');
                if(!$op->connection_fingerprint)$this->operations($id)->update(['connection_fingerprint'=>$this->connectionFingerprint($connection)]);
                $payload=['operacion'=>'consultar_comprobante','tipo_de_comprobante'=>3,'serie'=>$note->serie,'numero'=>(string)$note->numero];
            }
            $attempt=DB::table('sale_total_credit_attempts')->insertGetId(['operation_id'=>$op->id,'action'=>$payload['operacion'],
                'status'=>'processing','request_payload'=>json_encode($payload),'created_at'=>now(),'updated_at'=>now()]);
            $this->operations($id)->update(['status'=>'processing','updated_at'=>now()]);
            return ['blocked'=>false,'sale_id'=>$id,'note_id'=>$note->id,'operation_id'=>$op->id,'attempt_id'=>$attempt,
                'connection'=>$connection,'payload'=>$payload];
        });
        if($claim['blocked'])return $this->outcome($id,$files);
        $result=$claim['result']??null;$state=$claim['status']??'unknown';$httpStatus=null;
        if(!empty($claim['local_reply'])&&!$this->validResponse($claim,$result?:[]))throw new \DomainException('La respuesta guardada no coincide con el documento. Requiere conciliación.');
        if(empty($claim['local_reply'])){
            try{
                $response=Http::withHeaders(['Authorization'=>'Token token='.$claim['connection']->token(),'Accept'=>'application/json'])
                    ->timeout(60)->post($claim['connection']->url(),$claim['payload']);$httpStatus=$response->status();$body=$response->json();
                if(is_array($body)){
                    foreach(['invoice','data']as$k){if(isset($body[$k])&&is_array($body[$k]))$body=array_merge($body,$body[$k]);}
                    $result=$body;
                    if($response->successful()&&$this->validResponse($claim,$body)){
                        $code=(string)($body['sunat_responsecode']??'');
                        $state=filter_var($body['aceptada_por_sunat']??false,FILTER_VALIDATE_BOOLEAN)?'accepted':
                            (!empty($body['sunat_soap_error'])||($code!==''&&$code!=='0')?'rejected':'pending');
                    }
                }
            }catch(\Throwable $ignored){}
            // Durable evidence first: a failed Sale/Note update must not cause another generation.
            DB::table('sale_total_credit_attempts')->where('id',$claim['attempt_id'])->update(['status'=>$state,'http_status'=>$httpStatus,
                'response_payload'=>$result?json_encode($result):null,'updated_at'=>now()]);
        }
        DB::transaction(function()use($id,$claim,$state,$result){
            $sale=Sale::where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())
                ->where('branch_id',TenantContext::branchId())->whereKey($id)->lockForUpdate()->firstOrFail();
            $op=$this->operations($id)->lockForUpdate()->first();$note=$sale->creditNotes()->where('id',$claim['note_id'])->lockForUpdate()->firstOrFail();
            if(!$op||(int)$op->id!==(int)$claim['operation_id']||$op->status!=='processing')throw new \DomainException('La operación cambió; consulte su estado. No se volvió a emitir.');
            $diagnostic=$state==='unknown'?bin2hex(random_bytes(12)):null;
            $this->operations($id)->update(['status'=>$state,'diagnostic_reference'=>$diagnostic,'updated_at'=>now()]);
            if($state!=='unknown'){
                if(($note->serie&&(string)$note->serie!==(string)$result['serie'])||($note->numero&&(string)$note->numero!==(string)$result['numero']))throw new \DomainException('La identidad guardada de la NC no coincide. No se sobrescribió ni se reemitió.');
                $snapshot=json_decode($op->generation_payload?:'null',true);
                if($snapshot&&((string)$sale->serie_sunat!==(string)$snapshot['documento_que_se_modifica_serie']||(string)$sale->numero!==(string)$snapshot['documento_que_se_modifica_numero']||PartialCreditSettlement::cents($sale->importe_total)!==PartialCreditSettlement::cents($snapshot['total'])))throw new \DomainException('El comprobante original cambió y requiere conciliación.');
                $note->serie=$result['serie'];$note->numero=$result['numero'];$note->status=$state;
                $note->sunat_status=$state;$message=$result['sunat_description']??($result['sunat_note']??'');
                $note->sunat_message=is_scalar($message)?substr((string)$message,0,2000):'Resultado guardado para revisión.';
                $note->sunat_responsecode=$result['sunat_responsecode']??null;$note->sunat_ticket=$result['sunat_ticket']??($result['sunat_ticket_numero']??null);
                $note->nubefact_response=json_encode($result);if($state==='accepted')$note->accepted_at=$note->accepted_at?:now();$note->save();
            }
            $sale->annulment_type='credit_note';$sale->credit_note_status='total';$sale->annulment_status=$state==='accepted'?'accepted':'pending';
            if($state==='accepted'){$sale->state_annulled=1;$sale->annulment_accepted_at=$sale->annulment_accepted_at?:now();$sale->annulled_by=$sale->annulled_by?:auth()->id();}$sale->save();
        });
        return $this->outcome($id,$files);
    }
    private function connectionFingerprint($connection): string
    {
        // Token rotation is allowed; the issuer endpoint/RUC must remain the original one.
        return hash('sha256',rtrim($connection->url(),'/').'|'.$connection->ruc());
    }
    private function fingerprint(array $data): string
    {
        return hash('sha256',json_encode(['financial_token'=>(string)($data['financial_token']??''),
            'cash_box'=>empty($data['refund_cash_box_id'])?null:(int)$data['refund_cash_box_id'],
            'subtype'=>empty($data['refund_subtype_id'])?null:(int)$data['refund_subtype_id']]));
    }
    private function validResponse(array $claim,array $body): bool
    {
        foreach(['numero','serie','tipo_de_comprobante','codigo_unico','total','cliente_numero_de_documento','documento_que_se_modifica_serie','documento_que_se_modifica_numero','documento_que_se_modifica_tipo','moneda']as$key){if(isset($body[$key])&&!is_scalar($body[$key]))return false;}
        if(isset($body['total'])&&!is_numeric($body['total']))return false;
        if(!empty($body['errors'])||!preg_match('/^[1-9][0-9]*$/D',(string)($body['numero']??'')))return false;
        $op=$this->operations($claim['sale_id'])->first();$snapshot=json_decode($op->generation_payload?:'null',true);
        $expected=$claim['payload']['serie']??($snapshot['serie']??null);
        if(!$expected||($body['serie']??null)!==$expected)return false;
        if(isset($claim['payload']['numero'])&&$claim['payload']['numero']!==''&&(string)$body['numero']!==(string)$claim['payload']['numero'])return false;
        if(isset($body['tipo_de_comprobante'])&&(string)$body['tipo_de_comprobante']!=='3')return false;
        if(isset($body['codigo_unico'])&&(string)$body['codigo_unico']!==(string)$op->reference)return false;
        if(!$snapshot){
            $sale=$this->access($claim['sale_id']);$note=$this->note($sale);
            if(isset($body['total'])&&PartialCreditSettlement::cents($body['total'])!==PartialCreditSettlement::cents($note->importe_total))return false;
            $legacy=['documento_que_se_modifica_serie'=>$sale->serie_sunat,'documento_que_se_modifica_numero'=>$sale->numero,
                'documento_que_se_modifica_tipo'=>$sale->type_document==='01'?1:2,'cliente_numero_de_documento'=>$sale->numero_documento_cliente];
            foreach($legacy as$key=>$expectedValue){if(isset($body[$key])&&(string)$body[$key]!== (string)$expectedValue)return false;}
        }
        if($snapshot){foreach(['cliente_numero_de_documento','documento_que_se_modifica_serie','documento_que_se_modifica_numero','documento_que_se_modifica_tipo','moneda']as$k){if(isset($body[$k])&&(string)$body[$k]!== (string)$snapshot[$k])return false;}
            if(isset($body['total'])&&PartialCreditSettlement::cents($body['total'])!==PartialCreditSettlement::cents($snapshot['total']))return false;
        }
        return true;
    }
    private function outcome($id,?callable $files): array
    {
        $sale=$this->access($id);$note=$this->note($sale);$op=$this->operations($id)->first();
        if(!$note)throw new \DomainException('No existe una nota total registrada.');
        $state=$op?$op->status:$note->status;
        $messages=['accepted'=>'Nota de crédito total aceptada. No se repetirá la devolución ni el reingreso.',
            'pending'=>'NC total registrada. La reversión operativa está aplicada; SUNAT sigue pendiente.',
            'processing'=>'Hay una NC total en proceso. No se realizó otro envío.',
            'unknown'=>'La emisión de la NC total no quedó confirmada. Se conserva su referencia y la reversión operativa. No vuelva a emitir; consulte o concilie el resultado.',
            'rejected'=>'La NC total fue rechazada. Por política del negocio se conserva la reversión operativa; revise el caso, no genere otra automáticamente.'];
        $message=$messages[$state]??'Revise la nota total existente; no se generó otra.';
        if($note->serie&&$note->numero)$message.=' Documento: '.$note->serie.'-'.$note->numero.'.';
        if($state==='unknown'&&(!$note->serie||!$note->numero))$message.=' Sin número confirmado no se puede consultar por serie/número; requiere contrastar el envío con Nubefact.';
        if($files&&$note->nubefact_response){try{$files($note,json_decode($note->nubefact_response,true));}catch(\Throwable $ignored){$message.=' Algunos archivos están pendientes; recupérelos consultando esta misma nota.';}}
        return ['message'=>$message,'sale_id'=>$id,'credit_note_id'=>$note->id,'credit_note_type'=>'total','credit_note_status'=>$state,
            'internal_reversal_status'=>$sale->internal_reversal_status,'reference'=>$op?$op->reference:$note->generation_key,
            'serie'=>$note->serie,'numero'=>$note->numero];
    }
}
