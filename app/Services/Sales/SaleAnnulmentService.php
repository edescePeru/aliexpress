<?php
namespace App\Services\Sales;
use App\Sale;
use App\Support\TenantContext;
use App\Services\NubefactCompanyConfigService;
use Illuminate\Support\Facades\{DB,Http,Log};

class SaleAnnulmentService {
 private function operation($id){return DB::table('sale_annulment_operations')->where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())->where('branch_id',TenantContext::branchId())->where('sale_id',$id);}
 private function sale($id){return app(SaleListAccess::class)->sale($id);}
 public function preview($id):array {
  app(SaleListAccess::class)->permission('annul_puntoVenta');$s=$this->sale($id);
  if(!$this->operation($id)->exists()&&$s->serie_sunat&&$s->numero&&!$s->hasSunatError()&&!$s->isReceiptFromToday()&&!$s->isWithinAnnulmentDeadline()&&$s->internal_reversal_status!=='reversed')return ['sale_id'=>$s->id,'code'=>'VENTA - '.$s->id,'currency'=>$s->currency,'requires_credit_note'=>true,'financial_token'=>null,'refund_cents'=>0,'debt_cents'=>0,'channels'=>[]];
  return array_merge(app(SaleAnnulmentReversal::class)->preview($s),['sale_id'=>$s->id,'code'=>'VENTA - '.$s->id,'currency'=>$s->currency,'annulment_status'=>$s->annulment_status]);
 }
 public function execute($id,array $data,bool $consult=false,?callable $files=null):array {
  app(SaleListAccess::class)->permission('annul_puntoVenta');$this->sale($id);
  if(DB::transactionLevel()!==0)throw new \LogicException('La anulación debe iniciar fuera de otra transacción.');
  $claim=DB::transaction(function()use($id,$data,$consult){
   $s=Sale::where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())->where('branch_id',TenantContext::branchId())->whereKey($id)->lockForUpdate()->firstOrFail();
   $this->sale($id);
   if($s->requiresEmissionReview())throw new \DomainException('Debe conciliar la emisión del comprobante antes de anular.');
   $op=$this->operation($id)->lockForUpdate()->first();$newOperation=!$op;
   if($op&&$consult){
    $saved=DB::table('sale_annulment_attempts')->where('operation_id',$op->id)->latest('id')->first();
    if($saved&&in_array($saved->status,['accepted','pending','rejected'],true)&&$op->status==='processing')return ['blocked'=>false,'local_reply'=>true,'sale_id'=>$id,'reference'=>$op->reference,'result'=>json_decode($saved->response_payload,true),'status'=>$saved->status];
   }
   if($s->state_annulled||($op&&$op->status==='accepted'))return ['blocked'=>true,'sale_id'=>$id,'status'=>'accepted'];
   if(app(PartialCreditSettlement::class)->hasPartialReturn($s))throw new \DomainException('Esta venta tiene devoluciones parciales. Devuelva las cantidades restantes mediante notas parciales.');
   if($op&&(!$consult||($op->status==='processing'&&\Carbon\Carbon::parse($op->updated_at)->gt(now()->subSeconds(90)))))return ['blocked'=>true,'sale_id'=>$id,'status'=>$op->status];
   $notIssued=(!$s->serie_sunat&&!$s->numero)&&!$op&&app(ExistingSaleInvoice::class)->flags($s)['can_generate_invoice'];
   $electronic=!$s->hasNoElectronicDocument()&&!$notIssued&&!$s->hasSunatError();$waiting=$s->isReceiptFromToday();
   if($electronic&&(!$s->serie_sunat||!$s->numero))throw new \DomainException('Falta confirmar la identidad del comprobante antes de anular.');
   if(!$op&&$electronic&&!$waiting&&!$s->isWithinAnnulmentDeadline()){
    $s->annulment_status='requires_credit_note';$s->annulment_type='credit_note';$s->save();return ['blocked'=>true,'sale_id'=>$id,'status'=>'requires_credit_note'];
   }
   if(!$op){
    // A legacy pending request may only be consulted, never silently regenerated.
    $legacy=$consult&&in_array($s->annulment_status,['pending','unknown','rejected'],true);
    $opId=DB::table('sale_annulment_operations')->insertGetId(['tenant_id'=>$s->tenant_id,'company_id'=>$s->company_id,'branch_id'=>$s->branch_id,'sale_id'=>$s->id,'requested_by'=>auth()->id(),'reference'=>bin2hex(random_bytes(10)),'status'=>$legacy?'unknown':($electronic?($waiting?'waiting_sunat_process':'processing'):'accepted'),'reversal_state'=>$s->internal_reversal_status==='reversed'?'legacy_reversed':'none','created_at'=>now(),'updated_at'=>now()]);
    $op=$this->operation($id)->first();
    $s->annulment_requested_at=$s->annulment_requested_at?:now();$s->annulment_requested_by=$s->annulment_requested_by?:auth()->id();$s->annulment_reason='Anulación solicitada por el usuario; reversión operativa inmediata.';
    app(SaleAnnulmentReversal::class)->apply($s,$op,$data);
   }
   if(!$electronic){$s->state_annulled=1;$s->annulment_status='accepted';$s->annulment_type='internal';$s->annulment_accepted_at=now();$s->annulled_by=auth()->id();$s->save();return ['blocked'=>true,'sale_id'=>$id,'status'=>'accepted'];}
   if($op->status==='waiting_sunat_process'&&$waiting){$s->annulment_status='waiting_sunat_process';$s->annulment_type='nubefact_baja';$s->annulment_requested_at=$s->annulment_requested_at?:now();$s->annulment_requested_by=$s->annulment_requested_by?:auth()->id();$s->save();return ['blocked'=>true,'sale_id'=>$id,'status'=>'waiting_sunat_process'];}
   $generate=($newOperation&&$op->status==='processing')||($op->status==='waiting_sunat_process'&&$consult);
   if($generate&&!$s->isWithinAnnulmentDeadline()){$s->annulment_status='requires_credit_note';$s->save();$this->operation($id)->update(['status'=>'requires_credit_note','updated_at'=>now()]);return ['blocked'=>true,'sale_id'=>$id,'status'=>'requires_credit_note'];}
   $connection=app(NubefactCompanyConfigService::class)->forSale($s);
   $payload=['operacion'=>$generate?'generar_anulacion':'consultar_anulacion','tipo_de_comprobante'=>$s->type_document==='01'?'1':'2','serie'=>$s->serie_sunat,'numero'=>(string)$s->numero];
   if($generate){$payload['codigo_unico']=$op->reference;$payload['motivo']='Anulación de comprobante solicitada por el usuario';}
   $this->operation($id)->update(['status'=>'processing','request_payload'=>json_encode($payload),'updated_at'=>now()]);
   $s->annulment_status='pending';$s->annulment_type='nubefact_baja';$s->annulment_requested_at=$s->annulment_requested_at?:now();$s->annulment_requested_by=$s->annulment_requested_by?:auth()->id();$s->save();
   $attemptId=DB::table('sale_annulment_attempts')->insertGetId(['operation_id'=>$op->id,'reference'=>$op->reference,'action'=>$payload['operacion'],'status'=>'processing','request_payload'=>json_encode($payload),'created_at'=>now(),'updated_at'=>now()]);
   return ['blocked'=>false,'attempt_id'=>$attemptId,'sale_id'=>$id,'connection'=>$connection,'payload'=>$payload,'reference'=>$op->reference];
  });
  if($claim['blocked']){
   $out=$this->outcome($id,$claim['status']);$op=$this->operation($id)->first();
   if($files&&$op&&$op->response_payload){try{$files($this->sale($id),json_decode($op->response_payload,true));}catch(\Throwable $ignored){$out['message'].=' Algunos archivos siguen pendientes de recuperación.';}}
   return $out;
  }
  // No database transaction or automatic retry while contacting the provider.
  $result=$claim['result']??null;$status=$claim['status']??'unknown';$diagnostic=null;$httpStatus=null;
  if(empty($claim['local_reply'])){
  try{
   $r=Http::withHeaders(['Authorization'=>'Token token='.$claim['connection']->token(),'Accept'=>'application/json'])->timeout(60)->post($claim['connection']->url(),$claim['payload']);$httpStatus=$r->status();$body=$r->json();if(is_array($body))$result=$body;
   if($r->successful()&&is_array($body)&&empty($body['errors'])&&(array_key_exists('aceptada_por_sunat',$body)||isset($body['sunat_responsecode'])||isset($body['sunat_ticket'])||isset($body['sunat_ticket_numero']))){
    $result=$body;$accepted=($body['aceptada_por_sunat']??false)===true;
    $code=(string)($body['sunat_responsecode']??'');$status=$accepted?'accepted':(!empty($body['sunat_soap_error'])||($code!==''&&$code!=='0')?'rejected':'pending');
   }
  }catch(\Throwable $ignored){}
  DB::table('sale_annulment_attempts')->where('id',$claim['attempt_id'])->update(['status'=>$status,'http_status'=>$httpStatus,'response_payload'=>$result?json_encode($result):null,'updated_at'=>now()]);
  }
  if($status==='unknown')$diagnostic=bin2hex(random_bytes(12));
  DB::transaction(function()use($id,$claim,$result,$status,$diagnostic){
   $s=Sale::where('tenant_id',TenantContext::tenantId())->where('company_id',TenantContext::companyId())->where('branch_id',TenantContext::branchId())->whereKey($id)->lockForUpdate()->firstOrFail();$op=$this->operation($id)->lockForUpdate()->first();
   if(!$op||$op->reference!==$claim['reference']||$op->status!=='processing')throw new \DomainException('El estado cambió durante la consulta. No se repitió el envío.');
   $updates=['status'=>$status,'diagnostic_reference'=>$diagnostic,'updated_at'=>now()];if($result&&$status!=='unknown')$updates['response_payload']=json_encode($result);$this->operation($id)->update($updates);
   $s->annulment_status=$status;$s->annulment_key=$op->reference;if($result&&$status!=='unknown'){$s->annulment_response=json_encode($result);$s->annulment_ticket=$result['sunat_ticket_numero']??($result['sunat_ticket']??$s->annulment_ticket);$s->annulment_sunat_responsecode=$result['sunat_responsecode']??$s->annulment_sunat_responsecode;$description=$result['sunat_description']??($result['sunat_note']??'');$s->annulment_sunat_message=is_scalar($description)?substr((string)$description,0,2000):'Resultado guardado para revisión.';$s->annulment_error=$status==='rejected'?$s->annulment_sunat_message:null;}$s->annulment_sunat_status=$status;
   if($status==='accepted'){$s->state_annulled=1;$s->annulment_accepted_at=$s->annulment_accepted_at?:now();$s->annulled_by=$s->annulled_by?:auth()->id();}
   $s->save();
  });
  $out=$this->outcome($id,$status);if($result&&$files&&$status!=='unknown'){try{$files($this->sale($id),$result);}catch(\Throwable $e){$out['message'].=' Algunos archivos están pendientes; consulte para recuperarlos, sin generar otra anulación.';}}
  return $out;
 }
 private function outcome($id,string $status):array {
  $messages=['accepted'=>'Venta anulada. La reversión operativa no se repetirá.','waiting_sunat_process'=>'Reversión operativa aplicada. La boleta espera procesamiento para solicitar su baja; consulte su estado posteriormente.','processing'=>'Hay una solicitud en proceso. No se realizó otro envío.','pending'=>'Reversión operativa aplicada. La anulación electrónica sigue pendiente.','unknown'=>'La comunicación no confirmó el resultado. Se conserva la reversión operativa y la referencia; consulte antes de cualquier otro envío.','rejected'=>'La anulación electrónica fue rechazada. Por política del negocio se conserva la reversión operativa; revise el caso, no vuelva a generar la solicitud.','requires_credit_note'=>'El comprobante requiere una nota de crédito. No se envió otra solicitud de baja.'];
  $op=$this->operation($id)->first();
  return ['operation_reference'=>$op?$op->reference:null,'diagnostic_reference'=>$op?$op->diagnostic_reference:null,'message'=>($status==='unknown'&&$op?($messages[$status].' Referencia: '.$op->reference):($messages[$status]??'Revise la operación. No se realizó otro envío.')),'sale_id'=>$id,'annulment_status'=>$status,'pending_annulment'=>in_array($status,['pending','processing','unknown','waiting_sunat_process'],true),'internal_reversal_status'=>$this->sale($id)->internal_reversal_status];
 }
}
