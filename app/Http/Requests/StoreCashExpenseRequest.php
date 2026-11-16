<?php

namespace App\Http\Requests;

use App\Models\CashRegister;
use Illuminate\Foundation\Http\FormRequest;

class StoreCashExpenseRequest extends FormRequest
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
            'category' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
