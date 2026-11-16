<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\FinancialLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CashRegisterService
{
    public function openRegister(User $user, string $openingBalance): CashRegister
    {
        return DB::transaction(function () use ($user, $openingBalance): CashRegister {
            $existingRegister = CashRegister::query()
                ->whereDate('register_date', today())
                ->where('opened_by', $user->id)
                ->exists();

            if ($existingRegister) {
                throw ValidationException::withMessages([
                    'opening_balance' => 'You already opened a cash register for today.',
                ]);
            }

            return CashRegister::create([
                'opened_by' => $user->id,
                'register_date' => today(),
                'opening_balance' => $openingBalance,
                'status' => 'open',
                'opened_at' => now(),
            ]);
        });
    }

    /**
     * @param array{invoice_id: int, amount: string, description?: string|null} $data
     */
    public function recordPayment(User $user, CashRegister $register, array $data): CashTransaction
    {
        return DB::transaction(function () use ($user, $register, $data): CashTransaction {
            $register = $this->lockOpenRegister($user, $register);
            $invoiceReference = Invoice::query()->findOrFail($data['invoice_id']);
            $student = Student::query()->lockForUpdate()->findOrFail($invoiceReference->student_id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoiceReference->id);

            if (in_array($invoice->status, ['paid', 'void'], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice is not available for payment.',
                ]);
            }

            $outstanding = $invoice->outstandingAmountInMinorUnits();
            $amount = Money::minorUnits($data['amount']);

            if ($amount > $outstanding) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the invoice balance.',
                ]);
            }

            $receiptNumber = $this->receiptNumber();
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'recorded_by' => $user->id,
                'verified_by' => $user->id,
                'amount' => Money::fromMinorUnits($amount),
                'currency' => $invoice->currency,
                'method' => 'cash',
                'reference' => $receiptNumber,
                'status' => 'verified',
                'paid_at' => now(),
                'verified_at' => now(),
            ]);

            $previousBalance = FinancialLedger::query()
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('balance_after');
            $balance = Money::minorUnits((string) ($previousBalance ?? '0.00')) - $amount;

            FinancialLedger::create([
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'recorded_by' => $user->id,
                'debit' => '0.00',
                'credit' => Money::fromMinorUnits($amount),
                'balance_after' => Money::fromMinorUnits($balance),
                'description' => 'Cash receipt '.$receiptNumber.' for invoice '.$invoice->invoice_number,
                'posted_at' => now(),
            ]);

            $invoice->update([
                'status' => $amount === $outstanding ? 'paid' : 'partially_paid',
            ]);
            $student->update([
                'clearance_status' => $balance > 0 ? 'uncleared' : 'cleared',
            ]);

            return $register->transactions()->create([
                'payment_id' => $payment->id,
                'recorded_by' => $user->id,
                'transaction_type' => 'collection',
                'receipt_number' => $receiptNumber,
                'amount' => Money::fromMinorUnits($amount),
                'description' => $data['description'] ?? 'Student invoice '.$invoice->invoice_number,
                'posted_at' => now(),
            ]);
        });
    }

    /**
     * @param array{category: string, description: string, amount: string, reference?: string|null} $data
     */
    public function recordPayout(User $user, CashRegister $register, array $data): CashTransaction
    {
        return DB::transaction(function () use ($user, $register, $data): CashTransaction {
            $register = $this->lockOpenRegister($user, $register);
            $amount = Money::minorUnits($data['amount']);

            if ($amount > $register->expectedBalanceInMinorUnits()) {
                throw ValidationException::withMessages([
                    'amount' => 'The payout cannot exceed the cash currently available in the register.',
                ]);
            }

            $receiptNumber = $this->receiptNumber();
            $expense = Expense::create([
                'recorded_by' => $user->id,
                'category' => $data['category'],
                'description' => $data['description'],
                'amount' => Money::fromMinorUnits($amount),
                'currency' => 'UGX',
                'expense_date' => today(),
                'reference' => $data['reference'] ?? $receiptNumber,
            ]);

            return $register->transactions()->create([
                'expense_id' => $expense->id,
                'recorded_by' => $user->id,
                'transaction_type' => 'payout',
                'receipt_number' => $receiptNumber,
                'amount' => Money::fromMinorUnits($amount),
                'description' => $data['description'],
                'posted_at' => now(),
            ]);
        });
    }

    public function closeRegister(User $user, CashRegister $register, string $closingBalance): CashRegister
    {
        return DB::transaction(function () use ($user, $register, $closingBalance): CashRegister {
            $register = CashRegister::query()->lockForUpdate()->findOrFail($register->id);

            if ($register->opened_by !== $user->id || $register->status !== 'open') {
                throw ValidationException::withMessages([
                    'cash_register' => 'Only the cashier who opened an active register can close it.',
                ]);
            }

            $expectedBalance = $register->expectedBalanceInMinorUnits();
            $countedBalance = Money::minorUnits($closingBalance);
            $register->update([
                'closed_by' => $user->id,
                'expected_balance' => Money::fromMinorUnits($expectedBalance),
                'closing_balance' => Money::fromMinorUnits($countedBalance),
                'variance' => Money::fromMinorUnits($countedBalance - $expectedBalance),
                'status' => 'closed',
                'closed_at' => now(),
            ]);

            return $register;
        });
    }

    private function lockOpenRegister(User $user, CashRegister $register): CashRegister
    {
        $lockedRegister = CashRegister::query()->lockForUpdate()->findOrFail($register->id);

        if ($lockedRegister->opened_by !== $user->id || $lockedRegister->status !== 'open') {
            throw ValidationException::withMessages([
                'cash_register' => 'Open your own cash register before recording transactions.',
            ]);
        }

        return $lockedRegister;
    }

    private function receiptNumber(): string
    {
        return 'RCPT-'.today()->format('Ymd').'-'.Str::upper(Str::random(8));
    }
}
