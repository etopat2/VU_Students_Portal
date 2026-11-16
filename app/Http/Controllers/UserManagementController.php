<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(Request $request): View|Response
    {
        $users = User::query()->with('roles')->orderBy('name')->paginate(25)->withQueryString();
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $page = view('admin.users.index', compact('users', 'roles'));

        return $this->respond($request, $page, 'User management');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);
        $role = Role::query()->findOrFail($validated['role_id']);

        DB::transaction(function () use ($validated, $role): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => 'Password123',
                'role' => $role->name,
                'must_change_password' => true,
            ]);
            $user->roles()->sync([$role->id]);
        });

        ToastMagic::success('User account created. The user must change the temporary password when signing in.');

        return redirect()->route('users.index');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);
        $role = Role::query()->findOrFail($validated['role_id']);

        if ($user->hasRole('admin') && $role->name !== 'admin') {
            $adminCount = $this->adminCount();

            if ($adminCount <= 1 || $user->is(auth()->user())) {
                throw ValidationException::withMessages([
                    'role_id' => 'Keep at least one administrator and do not change your own administrator role.',
                ]);
            }
        }

        DB::transaction(function () use ($user, $role): void {
            $user->forceFill(['role' => $role->name])->save();
            $user->roles()->sync([$role->id]);
        });

        ToastMagic::success('User role updated.');

        return redirect()->route('users.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->is(auth()->user())) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account while signed in.',
            ]);
        }

        if ($user->hasRole('admin')) {
            $adminCount = $this->adminCount();

            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'The last administrator account cannot be deleted.',
                ]);
            }
        }

        $user->delete();

        ToastMagic::success('User account deleted.');

        return redirect()->route('users.index');
    }

    private function respond(Request $request, View $page, string $title): View|Response
    {
        if ($request->ajax()) {
            return response($page->renderSections()['content'])
                ->header('X-Page-Title', $title);
        }

        return $page;
    }

    private function adminCount(): int
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin'))
            ->orWhere(fn ($query) => $query->where('role', 'admin')->whereDoesntHave('roles'))
            ->count();
    }
}
