<?php
namespace App\Http\Requests;
class UpdatePaymentDeadlineRequest extends PaymentDeadlineContextRequest
{
    public function rules() { return array_merge($this->contextRules(), $this->fieldRules(), ['paymentDeadline_id' => $this->identifierRules()]); }
}
