<?php

namespace App\Http\Requests;

use App\Models\CashRegister;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $register = $this->route('cashRegister');

        return $this->user()?->hasRole('accountant')
            && $register instanceof CashRegister
            && $register->opened_by === $this->user()->id
            && $register->status === 'open';
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
