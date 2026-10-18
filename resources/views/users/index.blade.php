@extends('layouts.app')

@section('title', 'Users')

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">User accounts</h1>
            <p class="text-sm text-slate-500">Assign roles and suspend access. Suspended users are signed out of every session.</p>
        </div>
        <div class="flex gap-2 text-sm">
            @foreach (['' => 'All', 'active' => 'Active', 'suspended' => 'Suspended'] as $value => $label)
                <a href="{{ route('users.index', array_filter(['status' => $value])) }}"
                   class="rounded-lg px-3 py-1.5 {{ request('status', '') === $value ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">User</th>
                    <th class="px-4 py-3 font-medium">Roles</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr class="{{ $user->is_active ? '' : 'bg-rose-50/40' }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $user->name }}</div>
                            <div class="text-slate-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @can('users.manage')
                                <form method="POST" action="{{ route('users.roles.update', $user) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    @foreach ($roles as $role)
                                        <label class="flex items-center gap-1">
                                            <input type="checkbox" name="roles[]" value="{{ $role }}" @checked($user->hasRole($role)) class="rounded border-slate-300">
                                            <span>{{ $role }}</span>
                                        </label>
                                    @endforeach
                                    <button class="text-indigo-600 hover:underline">Save</button>
                                </form>
                            @else
                                {{ $user->getRoleNames()->join(', ') ?: '—' }}
                            @endcan
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-800">Active</span>
                            @else
                                <span class="rounded bg-rose-100 px-2 py-0.5 text-xs text-rose-800">Suspended</span>
                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $user->suspended_at?->diffForHumans() }}
                                    @if ($user->suspendedBy) by {{ $user->suspendedBy->name }} @endif
                                    @if ($user->suspension_reason) · {{ $user->suspension_reason }} @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @can('users.manage')
                                @if ($user->is_active && ! $user->is(auth()->user()))
                                    <form method="POST" action="{{ route('users.suspend', $user) }}" class="flex justify-end gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <input name="reason" placeholder="Reason (optional)" class="w-40 rounded border border-slate-300 px-2 py-1 text-xs">
                                        <button class="rounded bg-rose-600 px-3 py-1 text-xs font-medium text-white hover:bg-rose-700">Suspend</button>
                                    </form>
                                @elseif (! $user->is_active)
                                    <form method="POST" action="{{ route('users.activate', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="rounded bg-emerald-600 px-3 py-1 text-xs font-medium text-white hover:bg-emerald-700">Re-activate</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
@endsection
