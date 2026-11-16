@extends('layout.layout')

@section('title', 'Audit logs')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">SYSTEM ADMINISTRATION</p>
        <h2>Audit logs</h2>
        <p class="page-description">Review sensitive account, finance, and academic-record changes.</p>
    </div>
</section>

<section class="card academic-form-card mb-4">
    <div class="card-body">
        <form action="{{ route('audit-logs.index') }}" method="GET" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label class="form-label" for="filter_action">Action</label>
                <input class="form-control" id="filter_action" name="action" value="{{ $filters['action'] ?? '' }}" maxlength="100" placeholder="e.g. payment.created">
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="filter_user">User ID</label>
                <input class="form-control" id="filter_user" name="user_id" type="number" min="1" value="{{ $filters['user_id'] ?? '' }}">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="filter_date">Date</label>
                <input class="form-control" id="filter_date" name="date" type="date" value="{{ $filters['date'] ?? '' }}">
            </div>
            <div class="col-12 col-md-1"><button class="btn btn-primary w-100" type="submit">Filter</button></div>
        </form>
    </div>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Date &amp; time</th><th>User</th><th>Action</th><th>Record</th><th>Changes</th><th>IP address</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td><code>{{ $log->action }}</code></td>
                        <td>{{ class_basename($log->subject_type) }}{{ $log->subject_id ? ' #'.$log->subject_id : '' }}</td>
                        <td>
                            <details>
                                <summary>View changes</summary>
                                <div class="mt-2">
                                    @if ($log->old_values)
                                        <strong>Before</strong>
                                        <pre class="small text-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                    @if ($log->new_values)
                                        <strong>After</strong>
                                        <pre class="small text-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                </div>
                            </details>
                        </td>
                        <td>{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="6">No audit entries match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $logs->links() }}</div>
</section>
@endsection
