<?php

namespace App\Http\Requests;

use App\Models\CashRegister;
use Illuminate\Foundation\Http\FormRequest;

class CloseCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $register = $this->route('cashRegister');

        return $this->user()?->hasRole('accountant')
            && $register instanceof CashRegister
            && $register->opened_by === $this->user()->id;
    }

    public function rules(): array
    {
        return ['closing_balance' => ['required', 'numeric', 'min:0', 'decimal:0,2']];
    }
}
