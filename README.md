# Laravel ERP Permission Matrix

[![tests](https://github.com/Islam016/laravel-erp-permission-matrix/actions/workflows/tests.yml/badge.svg)](https://github.com/Islam016/laravel-erp-permission-matrix/actions/workflows/tests.yml)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![spatie/laravel-permission](https://img.shields.io/badge/spatie%2Flaravel--permission-6-blue)

Role-based access control for an ERP that keeps growing. Instead of hard-coding permission names across seeders and controllers, every module and the actions it supports live in **one registry file**. Permissions are generated from it, and admins grant them from a **module × action matrix** screen. Accounts can be **suspended** with an audit trail, and a suspended user is signed out of every session.

## Features

- **Module registry** (`config/modules.php`): one place that lists modules (employees, leaves, payroll, documents, assets, fleet…) and their actions (view, create, update, delete, approve, export, run). Arabic and English labels are included.
- **`php artisan permissions:sync`**: creates missing permissions as `{module}.{action}`, gives super roles everything, and with `--prune` removes permissions that were dropped from the registry.
- **Permission matrix UI**: a grid per role with one checkbox per supported action; click a row or column header to toggle it. Input is validated against the registry, so a crafted request cannot grant permissions that do not exist.
- **Protected super role**: `admin` always passes every check through `Gate::before`, including modules added later, and cannot be edited from the matrix, so nobody can lock the system out.
- **Account suspension**: stores who suspended the account, when and why; deletes the user's database sessions and remember token; and a middleware signs out any remaining session on the next request (JSON requests get a 403).
- **Login that respects suspension**: suspended users get a clear message, others get the usual generic error.

## How it fits together

```
config/modules.php ──► ModuleRegistry ──► permissions:sync ──► permissions table
                             │
                             └──► Role matrix screen ──► role_has_permissions
                                                            │
routes (permission:payroll.run) ◄── Spatie middleware ◄─────┘

SuspendUser action ──► users.is_active = false, sessions deleted
EnsureUserIsActive middleware ──► logs out any session still open
```

Adding a module is a config change:

```php
// config/modules.php
'contracts' => [
    'label' => ['en' => 'Contracts', 'ar' => 'العقود'],
    'actions' => ['view', 'create', 'approve'],
],
```

```bash
php artisan permissions:sync
```

Then protect routes with the generated names:

```php
Route::post('/payroll/run', RunPayrollController::class)->middleware('permission:payroll.run');
```

## Getting started

```bash
git clone https://github.com/Islam016/laravel-erp-permission-matrix.git
cd laravel-erp-permission-matrix
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Sign in as `admin@example.com` / `password` (full access) or `hr@example.com` / `password` (HR role).

## Tests

```bash
php artisan test
```

The feature tests cover permission syncing and pruning, saving the matrix (including ignored unsupported input), the locked admin role, 403s for users without access, suspension with session cleanup, sign-out on the next request, blocked sign-in for suspended users, self-suspension protection and re-activation.

## Project structure

| Path | Purpose |
| --- | --- |
| `config/modules.php` | Module and action registry |
| `app/Support/ModuleRegistry.php` | Permission names, labels and the role matrix |
| `app/Console/Commands/SyncPermissions.php` | `permissions:sync` command |
| `app/Http/Controllers/RolePermissionController.php` | Roles list, create role, edit and save the matrix |
| `app/Http/Controllers/UserAccountController.php` | User list, role assignment, suspend and re-activate |
| `app/Actions/SuspendUser.php` | Suspension logic and session cleanup |
| `app/Http/Middleware/EnsureUserIsActive.php` | Signs out suspended users |

## License

MIT © Islam Gaber
