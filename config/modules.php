<?php

/*
|--------------------------------------------------------------------------
| ERP module registry
|--------------------------------------------------------------------------
|
| The single source of truth for what can be permitted in the system.
| Every module lists the actions it supports. Permissions are generated
| from this file as "{module}.{action}" (for example "payroll.approve"),
| so adding a module here is all it takes to make it manageable from the
| role matrix screen after running `php artisan permissions:sync`.
|
*/

return [

    'actions' => [
        'view' => ['en' => 'View', 'ar' => 'عرض'],
        'create' => ['en' => 'Create', 'ar' => 'إضافة'],
        'update' => ['en' => 'Edit', 'ar' => 'تعديل'],
        'delete' => ['en' => 'Delete', 'ar' => 'حذف'],
        'approve' => ['en' => 'Approve', 'ar' => 'اعتماد'],
        'export' => ['en' => 'Export', 'ar' => 'تصدير'],
        'run' => ['en' => 'Run', 'ar' => 'تشغيل'],
        'manage' => ['en' => 'Manage', 'ar' => 'إدارة'],
    ],

    'modules' => [

        'employees' => [
            'label' => ['en' => 'Employees', 'ar' => 'الموظفون'],
            'actions' => ['view', 'create', 'update', 'delete', 'export'],
        ],

        'leaves' => [
            'label' => ['en' => 'Leaves', 'ar' => 'الإجازات'],
            'actions' => ['view', 'create', 'update', 'approve', 'export'],
        ],

        'documents' => [
            'label' => ['en' => 'Documents archive', 'ar' => 'أرشيف المستندات'],
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        'payroll' => [
            'label' => ['en' => 'Payroll', 'ar' => 'الرواتب'],
            'actions' => ['view', 'run', 'approve', 'export'],
        ],

        'assets' => [
            'label' => ['en' => 'Assets & custody', 'ar' => 'الأصول والعهد'],
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        'fleet' => [
            'label' => ['en' => 'Fleet', 'ar' => 'الأسطول'],
            'actions' => ['view', 'create', 'update', 'delete'],
        ],

        'roles' => [
            'label' => ['en' => 'Roles & permissions', 'ar' => 'الأدوار والصلاحيات'],
            'actions' => ['manage'],
        ],

        'users' => [
            'label' => ['en' => 'User accounts', 'ar' => 'حسابات المستخدمين'],
            'actions' => ['view', 'manage'],
        ],

    ],

    /*
    | Roles that always receive every permission and cannot be edited from
    | the matrix screen, so nobody can lock the system out by mistake.
    */
    'super_roles' => ['admin'],

];
