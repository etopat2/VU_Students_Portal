<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class FinancialStatementController extends Controller
{
    public function index(Request $request): View|Response
    {
        abort_unless($request->user()->hasAnyRole(['accountant', 'registrar']), 403);

        $students = Student::query()
            ->with(['primaryCourse:id,title', 'studyProgram:id,name', 'latestFinancialLedger'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return $this->respond($request, 'financial-statements.index', compact('students'), 'Financial statements');
    }

    public function show(Request $request, Student $student): View|Response
    {
        $this->authorize('viewFinancials', $student);
        $student->load(['primaryCourse:id,code,title', 'studyProgram:id,name']);
        $entries = $student->financialLedger()
            ->with(['invoice:id,invoice_number', 'payment:id,reference,method', 'recordedBy:id,name'])
            ->orderBy('id')
            ->paginate(50);

        $payableInvoices = collect();
        $pendingPayment = null;

        if ($request->user()->isStudent()) {
            $pendingPayment = Payment::query()
                ->where('student_id', $student->id)
                ->where('gateway', 'flutterwave')
                ->where('status', 'pending')
                ->where('created_at', '>', now()->subMinutes(45))
                ->latest('id')
                ->first(['id', 'reference']);

            $payableInvoices = $student->invoices()
                ->with(['payments' => fn ($query) => $query->where('status', 'verified')])
                ->where('currency', 'UGX')
                ->whereIn('status', ['issued', 'partially_paid'])
                ->orderBy('due_date')
                ->get()
                ->filter(fn (Invoice $invoice): bool => $invoice->outstandingAmountInMinorUnits() >= 100)
                ->map(function (Invoice $invoice): Invoice {
                    $invoice->setAttribute(
                        'outstanding_amount',
                        Money::fromMinorUnits($invoice->outstandingAmountInMinorUnits()),
                    );

                    return $invoice;
                });
        }

        return $this->respond(
            $request,
            'financial-statements.show',
            [
                'student' => $student,
                'entries' => $entries,
                'balance' => $student->currentLedgerBalance(),
                'payableInvoices' => $payableInvoices,
                'pendingPayment' => $pendingPayment,
            ],
            'Student financial statement',
        );
    }

    public function myStatement(Request $request): View|Response
    {
        $student = $request->user()->student()->firstOrFail();

        return $this->show($request, $student);
    }

    private function respond(Request $request, string $view, array $data, string $title): View|Response
    {
        $page = view($view, $data);

        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }
}
