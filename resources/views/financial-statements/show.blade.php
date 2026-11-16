@extends('layout.layout')

@section('title', 'Financial statement')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">STUDENT FINANCE</p>
        <h2>{{ $student->name }}</h2>
        <p class="page-description">{{ $student->student_number }} · {{ $student->primaryCourse?->title ?? 'Course not assigned' }} · {{ $student->studyProgram?->name ?? 'Study mode not assigned' }}</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if (auth()->user()->isStudent() && $payableInvoices->isNotEmpty())
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#payFeesModal">
                <i class="fa fa-credit-card me-1" aria-hidden="true"></i> Pay fees
            </button>
        @endif
        @if (auth()->user()->hasAnyRole(['accountant', 'registrar']))
            <a class="btn btn-outline-secondary" href="{{ route('financial-statements.index') }}" data-page-link>Back to statements</a>
        @endif
    </div>
</section>

<section class="card mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div><span class="text-secondary">Current ledger balance</span><h3 class="mt-1 mb-0">UGX {{ number_format((float) $balance, 2) }}</h3></div>
        <span class="badge {{ (float) $balance <= 0 ? 'text-bg-success' : 'text-bg-warning' }}">{{ (float) $balance <= 0 ? 'Financially cleared' : 'Outstanding balance' }}</span>
    </div>
</section>

@if (session('payment_status'))
    <div class="alert alert-info" role="status">{{ session('payment_status') }}</div>
@endif

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Reference</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Recorded by</th></tr></thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td>{{ $entry->posted_at->format('M j, Y H:i') }}</td>
                        <td>{{ $entry->invoice?->invoice_number ?? $entry->payment?->reference ?? '—' }}</td>
                        <td>{{ $entry->description }}</td>
                        <td>{{ number_format((float) $entry->debit, 2) }}</td>
                        <td>{{ number_format((float) $entry->credit, 2) }}</td>
                        <td>{{ number_format((float) $entry->balance_after, 2) }}</td>
                        <td>{{ $entry->recordedBy?->name ?? 'System' }}</td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="7">There are no financial entries for this student.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $entries->links() }}</div>
</section>

@php($showPayFeesModal = auth()->user()->isStudent() && ($payableInvoices->isNotEmpty() || $pendingPayment || session()->has('payment_ref')))
@if ($showPayFeesModal)
    <div class="modal fade" id="payFeesModal" tabindex="-1" aria-labelledby="payFeesModalLabel" aria-hidden="true"
        data-payment-ref="{{ session('payment_ref', $pendingPayment?->reference ?? '') }}"
        data-status-url-template="{{ str_replace('PAYMENT-REFERENCE', '__REFERENCE__', route('student.payments.status', ['reference' => 'PAYMENT-REFERENCE'])) }}">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="payFeesForm" action="{{ route('student.payments.checkout') }}" method="POST" data-pay-fees-form>
                    @csrf
                    <div class="modal-header">
                        <div>
                            <p class="eyebrow mb-1">STUDENT FINANCE</p>
                            <h2 class="modal-title fs-5" id="payFeesModalLabel">Pay fees</h2>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="payFeesFormFields">
                            <div class="mb-3">
                                <label class="form-label" for="payment_invoice_id">Invoice</label>
                                <select class="form-select" id="payment_invoice_id" name="invoice_id" required>
                                    @foreach ($payableInvoices as $invoice)
                                        <option value="{{ $invoice->id }}" data-balance="{{ $invoice->outstanding_amount }}" data-currency="{{ $invoice->currency }}">
                                            {{ $invoice->invoice_number }} — {{ $invoice->description }} ({{ $invoice->currency }} {{ number_format((float) $invoice->outstanding_amount, 0) }} outstanding)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="payment_amount">Amount (UGX)</label>
                                <input class="form-control" id="payment_amount" name="amount" type="number" min="1" step="1" inputmode="numeric" required>
                                <div class="form-text" id="payment_amount_help"></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="payment_method">Payment method</label>
                                <select class="form-select" id="payment_method" name="method" required>
                                    <option value="mobile_money">Mobile money</option>
                                    <option value="card">Card</option>
                                </select>
                            </div>
                            <div id="mobileMoneyFields">
                                <div class="mb-3">
                                    <label class="form-label" for="payment_network">Mobile money provider</label>
                                    <select class="form-select" id="payment_network" name="network">
                                        <option value="MTN">MTN Mobile Money</option>
                                        <option value="AIRTEL">Airtel Money</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label" for="payment_phone_number">Payment number</label>
                                    <input class="form-control" id="payment_phone_number" name="phone_number" type="tel" autocomplete="tel" placeholder="07XXXXXXXX or +2567XXXXXXXX" inputmode="tel">
                                    <div class="form-text">Enter the number that will receive the payment approval prompt.</div>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none" id="payFeesError" role="alert"></div>
                        </div>
                        <div class="d-none text-center py-4" id="payFeesWaiting" role="status" aria-live="polite">
                            <div class="spinner-border text-primary mb-3" id="payFeesSpinner" aria-hidden="true"></div>
                            <p class="mb-0" id="payFeesStatusMessage">Starting your payment…</p>
                            <a class="btn btn-outline-primary mt-3 d-none" id="paymentAuthorizationLink" href="#" target="_blank" rel="noopener">Open payment confirmation</a>
                            <button class="btn btn-outline-primary mt-3 d-none" type="button" id="payFeesTryAgain">Try another payment</button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-primary" id="payFeesSubmit" type="submit">
                            <span class="spinner-border spinner-border-sm me-1 d-none" id="payFeesSubmitSpinner" aria-hidden="true"></span>
                            Continue to payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
@endsection
