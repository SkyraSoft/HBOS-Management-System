<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('record supplier payments');
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|gt:0',
            'date' => 'nullable|date',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'financial_account_id' => 'nullable|integer',
            'idempotency_key' => 'nullable|string|max:100',
        ];
    }
}
