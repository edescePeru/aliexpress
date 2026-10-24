<?php
namespace App\Http\Requests;
class StorePaymentDeadlineRequest extends PaymentDeadlineContextRequest
{
    public function rules() { return array_merge($this->contextRules(), $this->fieldRules()); }
}
