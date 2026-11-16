@extends('layout.layout')

@section('title', 'Cash receipt')

@section('content')
<section class="page-intro d-print-none">
    <div>
        <p class="eyebrow">CASH MANAGEMENT</p>
        <h2>Receipt</h2>
        <p class="page-description">Receipt {{ $cashTransaction->receipt_number }}</p>
    </div>
    <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print receipt</button>
</section>

<article class="card mx-auto" style="max-width: 700px;">
    <div class="card-body p-4 p-md-5">
        <div class="d-flex justify-content-between gap-3 border-bottom pb-3 mb-4">
            <div><p class="eyebrow mb-1">VICTORIA UNIVERSITY</p><h3 class="h5 mb-0">Official cash receipt</h3></div>
            <span class="badge text-bg-success align-self-start">PAID</span>
        </div>
        <dl class="row mb-0">
            <dt class="col-5 col-md-4">Receipt number</dt><dd class="col-7 col-md-8">{{ $cashTransaction->receipt_number }}</dd>
            <dt class="col-5 col-md-4">Date</dt><dd class="col-7 col-md-8">{{ $cashTransaction->posted_at->format('M j, Y H:i') }}</dd>
            @if ($cashTransaction->payment)
                <dt class="col-5 col-md-4">Student</dt><dd class="col-7 col-md-8">{{ $cashTransaction->payment->student->name }} ({{ $cashTransaction->payment->student->student_number }})</dd>
                <dt class="col-5 col-md-4">Invoice</dt><dd class="col-7 col-md-8">{{ $cashTransaction->payment->invoice->invoice_number }}</dd>
            @endif
            <dt class="col-5 col-md-4">Description</dt><dd class="col-7 col-md-8">{{ $cashTransaction->description }}</dd>
            <dt class="col-5 col-md-4">Amount</dt><dd class="col-7 col-md-8 fw-bold">UGX {{ number_format((float) $cashTransaction->amount, 2) }}</dd>
            <dt class="col-5 col-md-4">Received by</dt><dd class="col-7 col-md-8">{{ $cashTransaction->recordedBy->name }}</dd>
        </dl>
        <p class="text-secondary small border-top pt-3 mt-4 mb-0">Please retain this receipt for your records.</p>
    </div>
</article>
@endsection
