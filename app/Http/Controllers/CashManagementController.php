<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\OpenCashRegisterRequest;
use App\Http\Requests\StoreCashExpenseRequest;
use App\Http\Requests\StoreCashPaymentRequest;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Invoice;
use App\Services\CashRegisterService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CashManagementController extends Controller
{
    public function index(Request $request): View|Response
    {
        $register = CashRegister::query()
            ->where('opened_by', $request->user()->id)
            ->whereDate('register_date', today())
            ->with(['transactions.payment.student', 'transactions.expense'])
            ->first();

        $invoices = Invoice::query()
            ->with([
                'student:id,name,student_number',
                'payments' => fn ($query) => $query
                    ->where('status', 'verified')
                    ->select('id', 'invoice_id', 'amount', 'status'),
            ])
            ->whereIn('status', ['issued', 'partially_paid'])
            ->orderBy('created_at')
            ->limit(100)
            ->get(['id', 'student_id', 'invoice_number', 'amount', 'currency', 'status']);

        foreach ($invoices as $invoice) {
            $invoice->setAttribute(
                'outstanding_amount',
                \App\Support\Money::fromMinorUnits($invoice->outstandingAmountInMinorUnits()),
            );
        }

        $registers = CashRegister::query()
            ->with('openedBy:id,name')
            ->where('opened_by', $request->user()->id)
            ->orderByDesc('register_date')
            ->limit(14)
            ->get();

        $expectedBalance = $register?->expectedBalanceInMinorUnits();
        $page = view('cash-management.index', [
            'register' => $register,
            'invoices' => $invoices,
            'registers' => $registers,
            'expectedBalance' => $expectedBalance === null
                ? null
                : \App\Support\Money::fromMinorUnits($expectedBalance),
        ]);

        return $this->respond($request, $page, 'Cash management');
    }

    public function open(
        OpenCashRegisterRequest $request,
        CashRegisterService $cashRegisterService,
    ): RedirectResponse {
        $register = $cashRegisterService->openRegister(
            $request->user(),
            (string) $request->validated('opening_balance'),
        );

        ToastMagic::success('Cash register opened for '.now()->toDateString().'.');

        return redirect()->route('cash-management.index', ['register' => $register->id]);
    }

    public function payment(
        StoreCashPaymentRequest $request,
        CashRegister $cashRegister,
        CashRegisterService $cashRegisterService,
    ): RedirectResponse {
        $transaction = $cashRegisterService->recordPayment(
            $request->user(),
            $cashRegister,
            $request->validated(),
        );

        ToastMagic::success('Cash payment received. Receipt '.$transaction->receipt_number.'.');

        return redirect()->route('cash-management.receipt', $transaction);
    }

    public function expense(
        StoreCashExpenseRequest $request,
        CashRegister $cashRegister,
        CashRegisterService $cashRegisterService,
    ): RedirectResponse {
        $cashRegisterService->recordPayout(
            $request->user(),
            $cashRegister,
            $request->validated(),
        );

        ToastMagic::success('Cash payout recorded.');

        return redirect()->route('cash-management.index');
    }

    public function close(
        CloseCashRegisterRequest $request,
        CashRegister $cashRegister,
        CashRegisterService $cashRegisterService,
    ): RedirectResponse {
        $closedRegister = $cashRegisterService->closeRegister(
            $request->user(),
            $cashRegister,
            (string) $request->validated('closing_balance'),
        );

        ToastMagic::success(
            'Cash register closed. Variance: UGX '.$closedRegister->variance.'.',
        );

        return redirect()->route('cash-management.index');
    }

    public function receipt(Request $request, CashTransaction $cashTransaction): View|Response
    {
        abort_unless(
            $request->user()->hasRole('accountant')
            && $cashTransaction->cashRegister()->where('opened_by', $request->user()->id)->exists(),
            404,
        );

        $cashTransaction->load([
            'cashRegister',
            'payment.student',
            'payment.invoice',
            'expense',
            'recordedBy',
        ]);

        $page = view('cash-management.receipt', compact('cashTransaction'));

        return $this->respond($request, $page, 'Cash receipt');
    }

    private function respond(Request $request, View $page, string $title): View|Response
    {
        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }
}
