@extends('layouts.app')

@section('title', 'Roles')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Roles &amp; permissions</h1>
            <p class="text-sm text-slate-500">{{ count($registry->modules()) }} modules · {{ $totalPermissions }} permissions defined in <code>config/modules.php</code></p>
        </div>

        <form method="POST" action="{{ route('roles.store') }}" class="flex gap-2">
            @csrf
            <input name="name" placeholder="new-role-name" required
                   class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add role</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Users</th>
                    <th class="px-4 py-3 font-medium">Permissions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ $role->name }}
                            @if ($registry->isSuperRole($role))
                                <span class="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs text-amber-800">full access</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $role->users_count }}</td>
                        <td class="px-4 py-3">
                            @php($share = $totalPermissions ? round($role->permissions_count / $totalPermissions * 100) : 0)
                            <div class="flex items-center gap-3">
                                <div class="h-2 w-32 overflow-hidden rounded bg-slate-100">
                                    <div class="h-2 bg-indigo-500" style="width: {{ $share }}%"></div>
                                </div>
                                <span class="text-slate-500">{{ $role->permissions_count }} / {{ $totalPermissions }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('roles.permissions.edit', $role) }}" class="text-indigo-600 hover:underline">Edit matrix</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
