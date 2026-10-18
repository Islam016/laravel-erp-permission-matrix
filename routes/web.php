<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\UserAccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::redirect('/', '/settings/roles');

    Route::prefix('settings')->group(function () {
        Route::middleware('permission:roles.manage')->group(function () {
            Route::get('/roles', [RolePermissionController::class, 'index'])->name('roles.index');
            Route::post('/roles', [RolePermissionController::class, 'store'])->name('roles.store');
            Route::get('/roles/{role}/permissions', [RolePermissionController::class, 'edit'])->name('roles.permissions.edit');
            Route::put('/roles/{role}/permissions', [RolePermissionController::class, 'update'])->name('roles.permissions.update');
        });

        Route::get('/users', [UserAccountController::class, 'index'])
            ->middleware('permission:users.view|users.manage')
            ->name('users.index');

        Route::middleware('permission:users.manage')->group(function () {
            Route::put('/users/{user}/roles', [UserAccountController::class, 'updateRoles'])->name('users.roles.update');
            Route::patch('/users/{user}/suspend', [UserAccountController::class, 'suspend'])->name('users.suspend');
            Route::patch('/users/{user}/activate', [UserAccountController::class, 'activate'])->name('users.activate');
        });
    });
});
