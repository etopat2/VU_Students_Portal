@extends('layout.layout')

@section('title', 'Cash management')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">FINANCE</p>
        <h2>Cash management</h2>
        <p class="page-description">Open the daily register, receive student fees, record petty cash, and reconcile at close.</p>
    </div>
</section>

@if (! $register)
    <section class="card academic-form-card mb-4">
        <div class="card-body">
            <h3 class="card-title">Open today's cash register</h3>
            <form action="{{ route('cash-management.open') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-md-5">
                    <label class="form-label" for="opening_balance">Opening cash balance (UGX)</label>
                    <input class="form-control @error('opening_balance') is-invalid @enderror" name="opening_balance" id="opening_balance" type="number" min="0" step="0.01" value="{{ old('opening_balance', '0.00') }}" required>
                </div>
                <div class="col-12 col-md-auto"><button class="btn btn-primary" type="submit">Open register</button></div>
            </form>
        </div>
    </section>
@else
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4"><article class="card h-100"><div class="card-body"><span class="text-secondary">Opening balance</span><h3 class="mt-2">UGX {{ number_format((float) $register->opening_balance, 2) }}</h3></div></article></div>
        <div class="col-12 col-md-4"><article class="card h-100"><div class="card-body"><span class="text-secondary">Expected cash</span><h3 class="mt-2">UGX {{ number_format((float) $expectedBalance, 2) }}</h3></div></article></div>
        <div class="col-12 col-md-4"><article class="card h-100"><div class="card-body"><span class="text-secondary">Register status</span><h3 class="mt-2">{{ ucfirst($register->status) }}</h3></div></article></div>
    </div>

    @if ($register->status === 'open')
        <div class="row g-3 mb-4">
            <div class="col-12 col-xl-6">
                <section class="card academic-form-card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Receive student payment</h3>
                        <form action="{{ route('cash-management.payments.store', $register) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="invoice_id">Student invoice</label>
                                <select class="form-select @error('invoice_id') is-invalid @enderror" id="invoice_id" name="invoice_id" required>
                                    <option value="">Select an outstanding invoice</option>
                                    @foreach ($invoices as $invoice)
                                        <option value="{{ $invoice->id }}" @selected((string) old('invoice_id') === (string) $invoice->id)>{{ $invoice->student->student_number }} · {{ $invoice->student->name }} · {{ $invoice->invoice_number }} · {{ $invoice->currency }} {{ $invoice->outstanding_amount }} outstanding</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="amount">Amount received (UGX)</label>
                                <input class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="payment_description">Note (optional)</label>
                                <input class="form-control" id="payment_description" name="description" maxlength="255" value="{{ old('description') }}">
                            </div>
                            <button class="btn btn-primary" type="submit">Record cash payment</button>
                        </form>
                    </div>
                </section>
            </div>
            <div class="col-12 col-xl-6">
                <section class="card academic-form-card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Record petty cash payout</h3>
                        <form action="{{ route('cash-management.expenses.store', $register) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="category">Expense category</label>
                                <input class="form-control @error('category') is-invalid @enderror" id="category" name="category" maxlength="100" value="{{ old('category') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="expense_description">Description</label>
                                <input class="form-control @error('description') is-invalid @enderror" id="expense_description" name="description" maxlength="255" value="{{ old('description') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="expense_amount">Amount paid (UGX)</label>
                                <input class="form-control @error('amount') is-invalid @enderror" id="expense_amount" name="amount" type="number" min="0.01" step="0.01" value="{{ old('amount') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="reference">Reference (optional)</label>
                                <input class="form-control" id="reference" name="reference" maxlength="100" value="{{ old('reference') }}">
                            </div>
                            <button class="btn btn-outline-primary" type="submit">Record payout</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>

        <section class="card academic-form-card mb-4">
            <div class="card-body">
                <h3 class="card-title">Close today's register</h3>
                <p class="text-secondary">Count the cash and enter the actual closing balance. Any variance will be recorded.</p>
                <form action="{{ route('cash-management.close', $register) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-12 col-md-5">
                        <label class="form-label" for="closing_balance">Counted cash (UGX)</label>
                        <input class="form-control @error('closing_balance') is-invalid @enderror" name="closing_balance" id="closing_balance" type="number" min="0" step="0.01" value="{{ old('closing_balance', $expectedBalance) }}" required>
                    </div>
                    <div class="col-12 col-md-auto"><button class="btn btn-outline-danger" type="submit">Close register</button></div>
                </form>
            </div>
        </section>
    @endif

    <section class="card academic-table-card mb-4">
        <div class="card-header bg-white"><h3 class="h5 mb-0">Today's cash activity</h3></div>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead><tr><th>Receipt</th><th>Type</th><th>Description</th><th>Amount</th><th>Time</th></tr></thead>
                <tbody>
                    @forelse ($register->transactions as $transaction)
                        <tr>
                            <td>
                                @if ($transaction->transaction_type === 'collection')
                                    <a href="{{ route('cash-management.receipt', $transaction) }}" target="_blank" rel="noopener">{{ $transaction->receipt_number }}</a>
                                @else
                                    {{ $transaction->receipt_number }}
                                @endif
                            </td>
                            <td>{{ ucfirst($transaction->transaction_type) }}</td>
                            <td>{{ $transaction->description }}</td>
                            <td>{{ number_format((float) $transaction->amount, 2) }}</td>
                            <td>{{ $transaction->posted_at->format('H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-secondary py-4" colspan="5">No cash has been collected or paid out today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endif

<section class="card academic-table-card">
    <div class="card-header bg-white"><h3 class="h5 mb-0">Recent registers</h3></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date</th><th>Opening</th><th>Closing</th><th>Variance</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($registers as $recentRegister)
                    <tr>
                        <td>{{ $recentRegister->register_date->format('M j, Y') }}</td>
                        <td>{{ number_format((float) $recentRegister->opening_balance, 2) }}</td>
                        <td>{{ $recentRegister->closing_balance === null ? '—' : number_format((float) $recentRegister->closing_balance, 2) }}</td>
                        <td>{{ $recentRegister->variance === null ? '—' : number_format((float) $recentRegister->variance, 2) }}</td>
                        <td>{{ ucfirst($recentRegister->status) }}</td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="5">No register history is available yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
