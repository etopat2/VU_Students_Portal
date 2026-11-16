<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('accountant') ?? false;
    }

    public function rules(): array
    {
        return ['opening_balance' => ['required', 'numeric', 'min:0', 'decimal:0,2']];
    }
}
