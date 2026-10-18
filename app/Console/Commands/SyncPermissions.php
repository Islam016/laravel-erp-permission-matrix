<?php

namespace App\Console\Commands;

use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync
                            {--prune : Delete permissions that are no longer defined in config/modules.php}';

    protected $description = 'Create the permissions defined by the module registry and give super roles all of them';

    public function handle(ModuleRegistry $registry, PermissionRegistrar $permissionRegistrar): int
    {
        $guard = config('auth.defaults.guard', 'web');
        $defined = $registry->permissionNames();

        $created = 0;
        foreach ($defined as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
            $created += $permission->wasRecentlyCreated ? 1 : 0;
        }

        $pruned = 0;
        if ($this->option('prune')) {
            $pruned = Permission::query()
                ->where('guard_name', $guard)
                ->whereNotIn('name', $defined)
                ->get()
                ->each->delete()
                ->count();
        }

        foreach (config('modules.super_roles', []) as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard])
                ->syncPermissions($defined->all());
        }

        $permissionRegistrar->forgetCachedPermissions();

        $this->components->info("Permissions synced: {$defined->count()} defined, {$created} created, {$pruned} pruned.");

        return self::SUCCESS;
    }
}
