<?php

namespace App\Services;

use App\Models\FinancialLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnlinePaymentSettlement
{
    /**
     * Verify critical provider details before atomically posting a payment.
     *
     * @param array<string, mixed> $transaction
     */
    public function settle(Payment $payment, array $transaction): bool
    {
        if (($transaction['status'] ?? null) !== 'successful'
            || (string) ($transaction['tx_ref'] ?? '') !== (string) $payment->reference
            || strtoupper((string) ($transaction['currency'] ?? '')) !== strtoupper($payment->currency)
            || Money::minorUnits((string) ($transaction['amount'] ?? '0')) !== Money::minorUnits((string) $payment->amount)
            || empty($transaction['id'])) {
            return false;
        }

        return DB::transaction(function () use ($payment, $transaction): bool {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);

            if ($lockedPayment->status === 'verified') {
                return true;
            }

            if ($lockedPayment->status !== 'pending') {
                return false;
            }

            $student = Student::query()->lockForUpdate()->findOrFail($lockedPayment->student_id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($lockedPayment->invoice_id);

            if (in_array($invoice->status, ['paid', 'void'], true)
                || $invoice->student_id !== $student->id
                || $invoice->currency !== $lockedPayment->currency
                || $invoice->outstandingAmountInMinorUnits() < Money::minorUnits((string) $lockedPayment->amount)) {
                throw ValidationException::withMessages([
                    'payment' => 'This invoice changed while the payment was processing. Contact the finance office.',
                ]);
            }

            $amount = Money::minorUnits((string) $lockedPayment->amount);
            $outstandingBeforePayment = $invoice->outstandingAmountInMinorUnits();
            $previousBalance = FinancialLedger::query()
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('balance_after');
            $balance = Money::minorUnits((string) ($previousBalance ?? '0.00')) - $amount;

            $paymentType = strtolower((string) ($transaction['payment_type'] ?? ''));
            $method = match ($paymentType) {
                'card' => 'card',
                'mobilemoneyuganda', 'mobile_money_uganda' => 'mobile_money',
                default => 'mobile_money',
            };

            $lockedPayment->update([
                'method' => $method,
                'gateway_transaction_id' => (string) $transaction['id'],
                'status' => 'verified',
                'paid_at' => $transaction['created_at'] ?? now(),
                'verified_at' => now(),
            ]);

            FinancialLedger::create([
                'student_id' => $student->id,
                'payment_id' => $lockedPayment->id,
                'recorded_by' => null,
                'debit' => '0.00',
                'credit' => Money::fromMinorUnits($amount),
                'balance_after' => Money::fromMinorUnits($balance),
                'description' => 'Flutterwave payment for '.$invoice->invoice_number,
                'posted_at' => now(),
            ]);

            $invoice->update([
                'status' => $amount === $outstandingBeforePayment ? 'paid' : 'partially_paid',
            ]);
            $student->update([
                'clearance_status' => $balance > 0 ? 'uncleared' : 'cleared',
            ]);

            return true;
        });
    }
}
