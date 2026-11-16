@extends('layout.layout')

@section('title', 'Bank & mobile payments')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">FINANCE</p>
        <h2>Bank &amp; mobile payments</h2>
        <p class="page-description">Record verified transfers and card payments against student invoices.</p>
    </div>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Invoice</th><th>Outstanding</th><th>Record payment</th></tr></thead>
            <tbody>
                @forelse ($invoices as $invoice)
                    <tr>
                        <td><strong>{{ $invoice->student->name }}</strong><span class="d-block text-secondary small">{{ $invoice->student->student_number }}</span></td>
                        <td>{{ $invoice->invoice_number }}<span class="d-block text-secondary small">{{ ucfirst(str_replace('_', ' ', $invoice->status)) }}</span></td>
                        <td>{{ $invoice->currency }} {{ $invoice->outstanding_amount }}</td>
                        <td>
                            <form action="{{ route('payments.store') }}" method="POST" class="row g-2 align-items-end">
                                @csrf
                                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                <div class="col-6 col-lg-3">
                                    <label class="form-label" for="amount_{{ $invoice->id }}">Amount</label>
                                    <input class="form-control @error('amount') is-invalid @enderror" id="amount_{{ $invoice->id }}" name="amount" type="number" min="0.01" max="{{ $invoice->outstanding_amount }}" step="0.01" value="{{ old('invoice_id') == $invoice->id ? old('amount') : '' }}" required>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <label class="form-label" for="method_{{ $invoice->id }}">Method</label>
                                    <select class="form-select" id="method_{{ $invoice->id }}" name="method" required>
                                        <option value="mobile_money">Mobile money</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                                <div class="col-8 col-lg-4">
                                    <label class="form-label" for="reference_{{ $invoice->id }}">Transaction reference</label>
                                    <input class="form-control @error('reference') is-invalid @enderror" id="reference_{{ $invoice->id }}" name="reference" maxlength="100" value="{{ old('invoice_id') == $invoice->id ? old('reference') : '' }}">
                                </div>
                                <div class="col-4 col-lg-2"><button class="btn btn-primary w-100" type="submit">Post</button></div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="4">No issued or partially paid invoices are waiting for payment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
