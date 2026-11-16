@extends('layout.layout')

@section('title', 'Users & roles')

@section('content')
<section class="page-intro">
    <div>
        <p class="eyebrow">SYSTEM ADMINISTRATION</p>
        <h2>Users &amp; roles</h2>
        <p class="page-description">Create accounts, assign roles, and manage access to the portal.</p>
    </div>
</section>

<section class="card academic-form-card mb-4">
    <div class="card-body">
        <h3 class="card-title">Create user account</h3>
        <p class="text-secondary">New accounts receive the temporary password <strong>Password123</strong> and must change it when they sign in.</p>
        <form action="{{ route('users.store') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-12 col-md-4">
                <label class="form-label" for="user_name">Name</label>
                <input class="form-control @error('name') is-invalid @enderror" id="user_name" name="name" value="{{ old('name') }}" maxlength="255" required>
            </div>
            <div class="col-12 col-md-4">
                <label class="form-label" for="user_email">Email</label>
                <input class="form-control @error('email') is-invalid @enderror" id="user_email" name="email" type="email" value="{{ old('email') }}" maxlength="255" required>
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label" for="new_user_role">Role</label>
                <select class="form-select @error('role_id') is-invalid @enderror" id="new_user_role" name="role_id" required>
                    <option value="">Select a role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-1"><button class="btn btn-primary w-100" type="submit">Add</button></div>
        </form>
    </div>
</section>

<section class="card academic-table-card">
    <div class="card-header bg-white"><h3 class="h5 mb-0">Portal accounts</h3></div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>User</th><th>Email</th><th>Current role</th><th>Access</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    @php($selectedRoleId = $user->roles->firstWhere('name', $user->role)?->id ?? $roles->firstWhere('name', $user->role)?->id)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->roles->pluck('name')->map(fn ($role) => ucfirst($role))->implode(', ') ?: ucfirst($user->role) }}</td>
                        <td>
                            @if ($user->must_change_password)
                                <span class="badge text-bg-warning">Password change required</span>
                            @else
                                <span class="badge text-bg-success">Active</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex flex-wrap justify-content-end gap-2">
                                <form action="{{ route('users.update-role', $user) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <label class="visually-hidden" for="role_{{ $user->id }}">Role for {{ $user->name }}</label>
                                    <select class="form-select form-select-sm" id="role_{{ $user->id }}" name="role_id" aria-label="Role for {{ $user->name }}">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" @selected($selectedRoleId === $role->id)>{{ ucfirst($role->name) }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary" type="submit">Save role</button>
                                </form>
                                <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete this account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" @disabled($user->is(auth()->user()))>Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="text-secondary py-4" colspan="5">No portal accounts are available.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $users->links() }}</div>
</section>
@endsection
