<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecordPaymentRequest;
use App\Models\FinancialLedger;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Support\Money;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request): View|Response
    {
        $invoices = Invoice::query()
            ->with([
                'student:id,name,student_number',
                'payments' => fn ($query) => $query
                    ->where('status', 'verified')
                    ->select('id', 'invoice_id', 'amount', 'status'),
            ])
            ->whereIn('status', ['issued', 'partially_paid'])
            ->orderBy('due_date')
            ->limit(100)
            ->get(['id', 'student_id', 'invoice_number', 'amount', 'currency', 'status', 'due_date']);

        foreach ($invoices as $invoice) {
            $invoice->setAttribute(
                'outstanding_amount',
                Money::fromMinorUnits($invoice->outstandingAmountInMinorUnits()),
            );
        }

        $page = view('finance.payments', compact('invoices'));

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', 'Bank & mobile payments');
        }

        return $page;
    }

    public function store(RecordPaymentRequest $request): RedirectResponse
    {
        $this->authorize('record', Payment::class);
        $this->authorize('verify', Payment::class);
        $validated = $request->validated();

        DB::transaction(function () use ($request, $validated): void {
            $invoiceReference = Invoice::query()->findOrFail($validated['invoice_id']);
            $student = Student::query()->lockForUpdate()->findOrFail($invoiceReference->student_id);
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($validated['invoice_id']);

            if (in_array($invoice->status, ['paid', 'void'], true)) {
                throw ValidationException::withMessages([
                    'invoice_id' => 'This invoice is not available for payment.',
                ]);
            }

            $verifiedAmount = $invoice->payments()
                ->where('status', 'verified')
                ->pluck('amount')
                ->reduce(
                    fn (int $total, string|int $amount): int => $total + Money::minorUnits((string) $amount),
                    0,
                );
            $outstanding = Money::minorUnits((string) $invoice->amount)
                - $verifiedAmount;
            $paymentAmount = Money::minorUnits((string) $validated['amount']);

            if ($paymentAmount > $outstanding) {
                throw ValidationException::withMessages([
                    'amount' => 'The payment cannot exceed the invoice balance.',
                ]);
            }

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'student_id' => $student->id,
                'recorded_by' => $request->user()->id,
                'verified_by' => $request->user()->id,
                'amount' => Money::fromMinorUnits($paymentAmount),
                'currency' => $invoice->currency,
                'method' => $validated['method'],
                'reference' => $validated['reference'] ?? null,
                'status' => 'verified',
                'paid_at' => $validated['paid_at'] ?? now(),
                'verified_at' => now(),
            ]);

            $previousBalance = FinancialLedger::query()
                ->where('student_id', $student->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->value('balance_after');
            $balanceInMinorUnits = Money::minorUnits((string) ($previousBalance ?? '0.00'))
                - $paymentAmount;

            FinancialLedger::create([
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'recorded_by' => $request->user()->id,
                'debit' => '0.00',
                'credit' => Money::fromMinorUnits($paymentAmount),
                'balance_after' => Money::fromMinorUnits($balanceInMinorUnits),
                'description' => 'Verified payment for '.$invoice->invoice_number,
                'posted_at' => now(),
            ]);

            $invoice->update([
                'status' => $paymentAmount === $outstanding ? 'paid' : 'partially_paid',
            ]);
            $student->update([
                'clearance_status' => $balanceInMinorUnits > 0 ? 'uncleared' : 'cleared',
            ]);
        });

        ToastMagic::success('Payment verified and posted to the student ledger.');

        return back();
    }
}
