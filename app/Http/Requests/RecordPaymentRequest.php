<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RecordPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('record', Payment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'method' => ['required', 'string', 'in:mobile_money,bank_transfer,card'],
            'reference' => ['nullable', 'string', 'max:100', 'unique:payments,reference'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $invoice = Invoice::query()->find($this->integer('invoice_id'));

                if (! $invoice || in_array($invoice->status, ['paid', 'void'], true)) {
                    $validator->errors()->add('invoice_id', 'This invoice is not available for payment.');

                    return;
                }

                $outstanding = $invoice->outstandingAmountInMinorUnits();

                if (Money::minorUnits((string) $this->input('amount')) > $outstanding) {
                    $validator->errors()->add('amount', 'The payment cannot exceed the invoice balance.');
                }
            },
        ];
    }
}
