<?php

namespace Tests\Unit;

use App\Support\ModuleRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ModuleRegistryTest extends TestCase
{
    private function registry(): ModuleRegistry
    {
        return new ModuleRegistry([
            'actions' => [
                'view' => ['en' => 'View', 'ar' => 'عرض'],
                'approve' => ['en' => 'Approve', 'ar' => 'اعتماد'],
                'delete' => ['en' => 'Delete', 'ar' => 'حذف'],
            ],
            'modules' => [
                'leaves' => ['label' => ['en' => 'Leaves', 'ar' => 'الإجازات'], 'actions' => ['view', 'approve']],
                'fleet' => ['label' => ['en' => 'Fleet'], 'actions' => ['view']],
            ],
            'super_roles' => ['admin'],
        ]);
    }

    public function test_it_builds_permission_names_from_modules_and_actions(): void
    {
        $this->assertSame(['leaves.view', 'leaves.approve', 'fleet.view'], $this->registry()->permissionNames()->all());
    }

    public function test_only_actions_used_by_a_module_become_matrix_columns(): void
    {
        $this->assertSame(['view', 'approve'], $this->registry()->actions());
    }

    public function test_it_ignores_unsupported_input(): void
    {
        $names = $this->registry()->permissionsFromInput([
            'leaves' => ['approve', 'delete', 'approve'],
            'fleet' => 'view',
            'payroll' => ['view'],
        ]);

        $this->assertSame(['leaves.approve', 'fleet.view'], $names);
    }

    public function test_labels_fall_back_to_english(): void
    {
        $registry = $this->registry();

        $this->assertSame('الإجازات', $registry->moduleLabel('leaves', 'ar'));
        $this->assertSame('Fleet', $registry->moduleLabel('fleet', 'ar'));
        $this->assertSame('اعتماد', $registry->actionLabel('approve', 'ar'));
    }

    public function test_super_roles_are_recognised_by_name(): void
    {
        $this->assertTrue($this->registry()->isSuperRole('admin'));
        $this->assertFalse($this->registry()->isSuperRole('hr'));
    }

    public function test_unknown_actions_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ModuleRegistry([
            'actions' => ['view' => ['en' => 'View']],
            'modules' => ['payroll' => ['label' => ['en' => 'Payroll'], 'actions' => ['run']]],
        ]);
    }
}
