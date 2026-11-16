@extends('layout.layout')

@section('title', $title)

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">ACADEMIC SETUP</p>
        <h2>{{ $title }}</h2>
        <p class="page-description">Manage the institution's academic structure.</p>
    </div>
    <a class="btn btn-primary" href="{{ route('academic-structure.create', ['type' => $type]) }}" data-page-link>Add {{ Str::singular($title) }}</a>
</section>

<section class="card academic-table-card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    @if (in_array($type, ['faculties', 'departments', 'modules'], true))
                        <th>{{ $type === 'faculties' ? 'Institution' : ($type === 'departments' ? 'Faculty' : 'Department') }}</th>
                    @endif
                    @if (in_array($type, ['faculties', 'departments', 'modules'], true))
                        <th>Code / details</th>
                    @endif
                    @if (in_array($type, ['study-programs', 'modules'], true))
                        <th>{{ $type === 'modules' ? 'Course awards' : 'Status' }}</th>
                    @endif
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr>
                        <td>
                            <strong>{{ $type === 'modules' ? $record->title : $record->name }}</strong>
                            @if ($type === 'modules')
                                <span class="d-block text-secondary small">{{ $record->code }} · {{ $record->credit_units }} credit units</span>
                            @endif
                        </td>
                        @if ($type === 'faculties')
                            <td>{{ $record->institution->name }}</td>
                            <td>{{ $record->code }}</td>
                        @elseif ($type === 'departments')
                            <td>{{ $record->faculty->name }}</td>
                            <td>{{ $record->code }}</td>
                        @elseif ($type === 'modules')
                            <td>{{ $record->department->name }}</td>
                            <td>Year {{ $record->year_level }} · Session {{ $record->session_number }}</td>
                        @endif
                        @if ($type === 'study-programs')
                            <td>{{ $record->is_active ? 'Active' : 'Inactive' }}</td>
                        @elseif ($type === 'modules')
                            <td>{{ $record->courses->pluck('code')->implode(', ') }}</td>
                        @endif
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('academic-structure.edit', ['type' => $type, 'record' => $record->id]) }}" data-page-link>Edit</a>
                            <form action="{{ route('academic-structure.destroy', ['type' => $type, 'record' => $record->id]) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="5">No records have been added yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $records->links() }}</div>
</section>
@endsection
