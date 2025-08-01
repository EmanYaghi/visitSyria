<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'card_holder'=>'required|string',
            'card_number'=>'required',
            'cvc'=>'required',
            'expiry_date'=>'required|date',
            'save_credit_card'=>'required|boolean',
        ];
    }
}
