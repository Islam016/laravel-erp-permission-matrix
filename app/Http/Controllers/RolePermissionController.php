<?php

namespace App\Http\Controllers;

use App\Support\ModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(private readonly ModuleRegistry $registry)
    {
    }

    public function index(): View
    {
        $roles = Role::query()->withCount(['permissions', 'users'])->orderBy('name')->get();

        return view('roles.index', [
            'roles' => $roles,
            'registry' => $this->registry,
            'totalPermissions' => $this->registry->permissionNames()->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('roles', 'name')],
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);

        return redirect()->route('roles.permissions.edit', $role)->with('status', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): View
    {
        return view('roles.matrix', [
            'role' => $role->load('permissions'),
            'registry' => $this->registry,
            'matrix' => $this->registry->matrixFor($role),
            'locked' => $this->registry->isSuperRole($role),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($this->registry->isSuperRole($role)) {
            return back()->withErrors(['role' => "The \"{$role->name}\" role always has every permission and cannot be edited."]);
        }

        $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['array'],
            'permissions.*.*' => ['string'],
        ]);

        $permissions = $this->registry->permissionsFromInput($request->input('permissions', []));

        $role->syncPermissions($permissions);

        return redirect()
            ->route('roles.permissions.edit', $role)
            ->with('status', count($permissions).' permissions saved for "'.$role->name.'".');
    }
}
