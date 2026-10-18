<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('permissions:sync')->assertSuccessful();
    }

    private function admin(): User
    {
        return User::factory()->create()->assignRole('admin');
    }

    public function test_sync_creates_one_permission_per_module_action(): void
    {
        $expected = app(ModuleRegistry::class)->permissionNames();

        $this->assertSame($expected->count(), Permission::count());
        $this->assertTrue(Permission::where('name', 'payroll.approve')->exists());
        $this->assertFalse(Permission::where('name', 'payroll.delete')->exists());
    }

    public function test_sync_is_idempotent_and_can_prune_removed_permissions(): void
    {
        Permission::create(['name' => 'legacy.view', 'guard_name' => 'web']);

        $this->artisan('permissions:sync')->assertSuccessful();
        $this->assertTrue(Permission::where('name', 'legacy.view')->exists());

        $this->artisan('permissions:sync', ['--prune' => true])->assertSuccessful();
        $this->assertFalse(Permission::where('name', 'legacy.view')->exists());
        $this->assertSame(app(ModuleRegistry::class)->permissionNames()->count(), Permission::count());
    }

    public function test_admin_role_receives_every_permission(): void
    {
        $admin = Role::findByName('admin');

        $this->assertSame(Permission::count(), $admin->permissions()->count());
    }

    public function test_matrix_page_lists_modules_and_actions(): void
    {
        $role = Role::create(['name' => 'hr', 'guard_name' => 'web']);
        $role->givePermissionTo('leaves.approve');

        $this->actingAs($this->admin())
            ->get(route('roles.permissions.edit', $role))
            ->assertOk()
            ->assertSee('Permission matrix: hr')
            ->assertSee('Payroll')
            ->assertSee('الرواتب')
            ->assertSee('aria-label="leaves.approve"', false);
    }

    public function test_saving_the_matrix_syncs_role_permissions(): void
    {
        $role = Role::create(['name' => 'hr', 'guard_name' => 'web']);
        $role->givePermissionTo('fleet.view');

        $this->actingAs($this->admin())
            ->put(route('roles.permissions.update', $role), [
                'permissions' => [
                    'employees' => ['view', 'create'],
                    'leaves' => ['approve'],
                    'payroll' => ['delete'],       // not supported by the module: ignored
                    'unknown' => ['view'],         // not in the registry: ignored
                ],
            ])
            ->assertRedirect(route('roles.permissions.edit', $role));

        $this->assertEqualsCanonicalizing(
            ['employees.view', 'employees.create', 'leaves.approve'],
            $role->fresh()->permissions->pluck('name')->all(),
        );
    }

    public function test_super_role_cannot_be_edited_from_the_matrix(): void
    {
        $admin = Role::findByName('admin');

        $this->actingAs($this->admin())
            ->put(route('roles.permissions.update', $admin), ['permissions' => []])
            ->assertSessionHasErrors('role');

        $this->assertSame(Permission::count(), $admin->fresh()->permissions()->count());
    }

    public function test_users_without_permission_cannot_open_the_matrix(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'hr', 'guard_name' => 'web']);

        $this->actingAs($user)->get(route('roles.index'))->assertForbidden();
        $this->actingAs($user)->get(route('roles.permissions.edit', $role))->assertForbidden();
    }

    public function test_granted_permission_opens_the_screen(): void
    {
        $role = Role::create(['name' => 'it', 'guard_name' => 'web']);
        $role->givePermissionTo('roles.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $user = User::factory()->create()->assignRole('it');

        $this->actingAs($user)->get(route('roles.index'))->assertOk()->assertSee('it');
    }

    public function test_admin_can_create_a_role(): void
    {
        $this->actingAs($this->admin())
            ->post(route('roles.store'), ['name' => 'auditor'])
            ->assertRedirect();

        $this->assertTrue(Role::where('name', 'auditor')->exists());
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }
}
