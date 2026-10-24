<?php
namespace App\Http\Requests;
class DeletePaymentDeadlineRequest extends PaymentDeadlineContextRequest
{
    public function rules() { return array_merge($this->contextRules(), ['paymentDeadline_id' => $this->identifierRules()]); }
}
