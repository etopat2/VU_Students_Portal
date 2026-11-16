@extends('layout.layout')

@section('title', 'Financial statements')

@section('content')
<section class="page-intro">
    <div><p class="eyebrow">FINANCE</p><h2>Student financial statements</h2><p class="page-description">Review current balances and open a student's ledger statement.</p></div>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Student</th><th>Course</th><th>Study program</th><th>Ledger balance</th><th>Clearance</th><th></th></tr></thead>
            <tbody>
                @forelse ($students as $student)
                    @php($balance = $student->latestFinancialLedger?->balance_after ?? '0.00')
                    <tr>
                        <td><strong>{{ $student->name }}</strong><span class="d-block text-secondary small">{{ $student->student_number }}</span></td>
                        <td>{{ $student->primaryCourse?->title ?? 'Not assigned' }}</td>
                        <td>{{ $student->studyProgram?->name ?? 'Not assigned' }}</td>
                        <td>UGX {{ number_format((float) $balance, 2) }}</td>
                        <td><span class="badge {{ (float) $balance <= 0 ? 'text-bg-success' : 'text-bg-warning' }}">{{ (float) $balance <= 0 ? 'Cleared' : 'Uncleared' }}</span></td>
                        <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('financial-statements.show', $student) }}" data-page-link>View statement</a></td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="6">No student accounts are available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $students->links() }}</div>
</section>
@endsection
