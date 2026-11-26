<?php
namespace App\Services\Sales;
use App\{Sale,SalePartialPayment,CashMovement,OutputDetail,StockLot,CreditNote,CreditNoteDetail,InventoryLevel,Item};
use Illuminate\Support\Facades\DB;

/** Local, atomic operational reversal. Never calls a provider. */
class SaleAnnulmentReversal {
 private function movements(Sale $s){return CashMovement::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('branch_id',$s->branch_id)->where('sale_id',$s->id);}
 public function financial(Sale $s):array {
  $partial=$s->pagos_parciales_venta==='s';
  $payments=SalePartialPayment::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('branch_id',$s->branch_id)->where('sale_id',$s->id)->where('state',1)->get();
  $sources=$this->movements($s)->whereIn('type',['sale','income'])->whereNull('regularization_source_id')->whereNull('cash_movement_origin_id')->orderBy('id')->lockForUpdate()->get();
  $active=$payments->pluck('cash_movement_id')->all();$paid=0;$refundable=0;$pending=[];$evidence=[];
  foreach($sources as$m){
   if($partial&&!in_array($m->id,$active))continue;
   $amount=PartialCreditSettlement::cents($m->amount);if($amount<=0)throw new \DomainException('El cobro no conserva un importe válido.');
   $register=DB::table('cash_registers')->where('id',$m->cash_register_id)->where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('branch_id',$s->branch_id)->where('cash_box_id',$m->cash_box_id)->first();
   if(!$register||!DB::table('cash_boxes')->where('id',$m->cash_box_id)->where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('currency',$s->currency)->where(function($q)use($s){$q->whereNull('branch_id')->orWhere('branch_id',$s->branch_id);})->exists())throw new \DomainException('El cobro necesita conciliar su caja y sesión originales.');
   $paid+=$amount;
   if(!$m->regularize){
    if($m->balance_effect!==false)throw new \DomainException('No se puede confirmar que el cobro pendiente no afectó saldo.');
    $pending[]=$m->id;
   }else{
    if($m->balance_effect===false){
     $posting=$this->movements($s)->whereKey($m->cash_movement_regularize_id)->where('regularization_source_id',$m->id)->where('cash_movement_origin_id',$m->id)->where('cash_box_id',$m->cash_box_id)->where('balance_effect',true)->where('regularize',true)->first();
     if(!$posting||$posting->type!==$m->type||($m->amount_regularize!==null&&PartialCreditSettlement::cents($posting->amount)!==PartialCreditSettlement::cents($m->amount_regularize)))throw new \DomainException('El cobro regularizado no conserva su abono vinculado.');
    }
    $refundable+=$amount;
   }
   $evidence[]=['movement_id'=>$m->id,'amount_cents'=>$amount,'pending'=>!$m->regularize];
  }
  $change=PartialCreditSettlement::cents($s->vuelto);$paid-=$change;$refundable-=$change;
  $total=PartialCreditSettlement::cents($s->importe_total);
  if($partial&&PartialCreditSettlement::cents($payments->sum('amount'))!==$paid)throw new \DomainException('Los abonos no coinciden con sus movimientos; requieren conciliación.');
  if($paid<0||$paid>$total||$refundable<0||$refundable>$paid||(!$partial&&$paid!==$total))throw new \DomainException('Los cobros y el vuelto necesitan conciliación antes de anular.');
  return ['original_cents'=>$total,'collected_cents'=>$paid,'refund_cents'=>$refundable,'debt_cents'=>$total-$paid,'pending_ids'=>$pending,'evidence'=>$evidence];
 }
 public function preview(Sale $s):array {
  if($s->internal_reversal_status==='reversed')return ['already_reversed'=>true,'refund_cents'=>0,'debt_cents'=>0,'channels'=>[],'financial_token'=>null];
  if(app(PartialCreditSettlement::class)->hasPartialReturn($s))throw new \DomainException('Esta venta tiene devoluciones parciales. Use las cantidades restantes mediante notas parciales.');
  $f=$this->financial($s);return array_merge($f,['financial_token'=>$this->token($f),'channels'=>app(PartialCreditSettlement::class)->channels($s),'already_reversed'=>false]);
 }
 private function token(array $f):string{return hash('sha256',json_encode($f));}
 public function apply(Sale $s,$operation,array $data):void {
  if(DB::transactionLevel()<1)throw new \LogicException('La reversión requiere transacción.');
  if($s->internal_reversal_status==='reversed')return;
  if(app(PartialCreditSettlement::class)->hasPartialReturn($s))throw new \DomainException('No se puede anular una venta con devoluciones parciales.');
  $f=$this->financial($s);
  if(!isset($data['financial_token'])||!hash_equals($this->token($f),(string)$data['financial_token']))throw new \DomainException('Cambió el saldo de la venta. Abra nuevamente la confirmación.');
  $session=$f['refund_cents']>0?app(PartialCreditSettlement::class)->destination($s,$data['refund_cash_box_id']??null,$data['refund_subtype_id']??null):null;
  $date=now();$keys=[];$itemized=false;
  foreach($s->details()->where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->get()as$d){
   $outputs=OutputDetail::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('sale_detail_id',$d->id)->orderBy('id')->lockForUpdate()->get();
   $tracked=DB::table('stock_items')->where('id',$d->stock_item_id)->where('tenant_id',$s->tenant_id)->value('tracks_inventory');
   if(($outputs->isNotEmpty()||$tracked)&&bccomp((string)$outputs->sum('percentage'),(string)$d->quantity,6)!==0)throw new \DomainException('Las salidas originales no cubren las unidades vendidas; requieren conciliación.');
   foreach($outputs as$o){
    $lot=StockLot::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->whereKey($o->stock_lot_id)->lockForUpdate()->first();
    if(!$lot)throw new \DomainException('No existe el lote original para registrar el reingreso.');
    // Reuse the proven historical provenance/cost validator, without creating a credit note.
    $note=new CreditNote(['sale_id'=>$s->id,'credit_note_type'=>'partial']);$note->id=0;$note->setRelation('sale',$s);
    $nd=new CreditNoteDetail(['credit_note_id'=>0,'sale_detail_id'=>$d->id]);$nd->setRelation('saleDetail',$d);
    $row=app(\App\Services\Inventory\CreditNoteReturnLedger::class)->row($note,$nd,$o,$lot,$o->percentage,$date);
    unset($row['credit_note_id'],$row['credit_note_detail_id']);$row['operation_id']=$operation->id;$row['sale_detail_id']=$d->id;$row['recorded_by']=auth()->id();$row['created_at']=$date;$row['updated_at']=$date;
    DB::table('sale_annulment_stock_returns')->insert($row);
    $expected=bcadd((string)$lot->qty_on_hand,$row['quantity'],6);$lot->qty_on_hand=$expected;$lot->save();$lot->refresh();if(bccomp((string)$lot->qty_on_hand,$expected,6)!==0)throw new \DomainException('La cantidad no cabe en la precisión del lote. No se aplicó la anulación.');
    if($o->item_id){$item=Item::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->whereKey($o->item_id)->lockForUpdate()->first();if(!$item||$item->state_item!=='exited'||(int)$item->stock_lot_id!==(int)$lot->id||(int)$item->stock_item_id!==(int)$lot->stock_item_id)throw new \DomainException('El ítem físico ya cambió de estado o empresa.');$item->state_item='entered';$item->save();$itemized=true;}
    $keys[$lot->stock_item_id.'|'.$lot->warehouse_id.'|'.$lot->location_id]=[$lot->stock_item_id,$lot->warehouse_id,$lot->location_id];
   }
  }
  foreach($keys as$k){$q=StockLot::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('stock_item_id',$k[0])->where('warehouse_id',$k[1])->where('location_id',$k[2]);$level=InventoryLevel::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('stock_item_id',$k[0])->where('warehouse_id',$k[1])->where('location_id',$k[2])->lockForUpdate()->first();if(!$level)$level=new InventoryLevel(['tenant_id'=>$s->tenant_id,'company_id'=>$s->company_id,'stock_item_id'=>$k[0],'warehouse_id'=>$k[1],'location_id'=>$k[2]]);$expectedLevel=(string)(clone$q)->sum('qty_on_hand');$level->qty_on_hand=$expectedLevel;$level->qty_reserved=(clone$q)->sum('qty_reserved');$level->save();$level->refresh();if(bccomp((string)$level->qty_on_hand,$expectedLevel,6)!==0)throw new \DomainException('El nivel de inventario no conserva la cantidad exacta del reingreso.');}
  foreach($f['pending_ids']as$id){$this->movements($s)->whereKey($id)->firstOrFail()->cancelPendingDeferred('sale_reversal');}
  $movement=null;
  if($session){$amount=$f['refund_cents']/100;$movement=CashMovement::create(['tenant_id'=>$s->tenant_id,'company_id'=>$s->company_id,'branch_id'=>$s->branch_id,'user_id'=>auth()->id(),'cash_box_id'=>$session->cash_box_id,'cash_register_id'=>$session->id,'cash_box_subtype_id'=>$data['refund_subtype_id']??null,'sale_id'=>$s->id,'type'=>'expense','amount'=>$amount,'regularize'=>true,'balance_effect'=>true,'arqueo'=>false,'description'=>'Devolución por anulación de venta #'.$s->id.' · operación #'.$operation->id]);$session->current_balance=bcsub((string)$session->current_balance,(string)$amount,2);$session->total_expenses=bcadd((string)($session->total_expenses??0),(string)$amount,2);$session->save();}
  SalePartialPayment::where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->where('branch_id',$s->branch_id)->where('sale_id',$s->id)->where('state',1)->update(['state'=>0]);
  if($s->quote_id){
   $quote=DB::table('quotes')->where('id',$s->quote_id)->where('tenant_id',$s->tenant_id)->where('company_id',$s->company_id)->lockForUpdate()->first();
   if(!$quote)throw new \DomainException('La cotización de origen no corresponde a la empresa.');
   DB::table('quotes')->where('id',$quote->id)->update(['state'=>$itemized?'canceled':'requote']);
   \App\QuoteMaterialReservation::where('tenant_id',$s->tenant_id)->where('quote_id',$quote->id)->lockForUpdate()->delete();
   \App\QuoteStockLot::where('tenant_id',$s->tenant_id)->where('quote_id',$quote->id)->lockForUpdate()->delete();
  }
  DB::table('sale_annulment_operations')->where('id',$operation->id)->update(['reversal_state'=>'reversed','refund_amount'=>$f['refund_cents']/100,'debt_cancelled'=>$f['debt_cents']/100,'refund_cash_box_id'=>$session?$session->cash_box_id:null,'refund_subtype_id'=>$session?($data['refund_subtype_id']??null):null,'refund_movement_id'=>$movement?$movement->id:null,'financial_evidence'=>json_encode($f),'updated_at'=>now()]);
  $s->internal_reversal_status='reversed';$s->internal_reversed_at=now();$s->internal_reversed_by=auth()->id();$s->save();
 }
}
