<?php

namespace App\Http\Controllers;

use App\Actions\SuspendUser;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserAccountController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles', 'suspendedBy')
            ->when($request->query('status') === 'suspended', fn ($q) => $q->where('is_active', false))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function updateRoles(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($data['roles'] ?? []);

        return back()->with('status', "Roles updated for {$user->name}.");
    }

    public function suspend(Request $request, User $user, SuspendUser $action): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $action->suspend($user, $request->user(), $data['reason'] ?? null);

        return back()->with('status', "{$user->name} has been suspended and signed out.");
    }

    public function activate(User $user, SuspendUser $action): RedirectResponse
    {
        $action->activate($user);

        return back()->with('status', "{$user->name} can sign in again.");
    }
}
