@extends('layouts.app')

@section('title', "Permissions · {$role->name}")

@section('content')
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ route('roles.index') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; All roles</a>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Permission matrix: {{ $role->name }}</h1>
            <p class="text-sm text-slate-500">Tick what this role can do in each module. Rows and columns can be toggled at once.</p>
        </div>
    </div>

    @if ($locked)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            This role always has every permission, including modules added later. It cannot be edited here.
        </div>
    @endif

    <form method="POST" action="{{ route('roles.permissions.update', $role) }}" id="matrix">
        @csrf
        @method('PUT')

        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Module</th>
                        @foreach ($registry->actions() as $action)
                            <th class="px-3 py-3 text-center font-medium">
                                <button type="button" class="hover:text-indigo-600" data-toggle-column="{{ $action }}" @disabled($locked)>
                                    {{ $registry->actionLabel($action) }}
                                    <span class="block text-xs font-normal text-slate-400">{{ $registry->actionLabel($action, 'ar') }}</span>
                                </button>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($matrix as $module => $actions)
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-slate-900">
                                <button type="button" class="text-left hover:text-indigo-600" data-toggle-row="{{ $module }}" @disabled($locked)>
                                    {{ $registry->moduleLabel($module) }}
                                    <span class="block text-xs font-normal text-slate-400">{{ $registry->moduleLabel($module, 'ar') }} · <code>{{ $module }}</code></span>
                                </button>
                            </th>
                            @foreach ($actions as $action => $granted)
                                <td class="px-3 py-3 text-center">
                                    @if ($granted === null)
                                        <span class="text-slate-300">–</span>
                                    @else
                                        <input type="checkbox"
                                               name="permissions[{{ $module }}][]"
                                               value="{{ $action }}"
                                               data-module="{{ $module }}"
                                               data-action="{{ $action }}"
                                               aria-label="{{ $module }}.{{ $action }}"
                                               class="h-4 w-4 rounded border-slate-300 text-indigo-600"
                                               @checked($locked || $granted)
                                               @disabled($locked)>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @unless ($locked)
            <div class="mt-6 flex justify-end">
                <button class="rounded-lg bg-indigo-600 px-5 py-2 font-medium text-white hover:bg-indigo-700">Save permissions</button>
            </div>
        @endunless
    </form>

    <script>
        const boxes = (selector) => [...document.querySelectorAll('#matrix input[type=checkbox]' + selector)];
        const toggle = (list) => {
            const allOn = list.every((box) => box.checked);
            list.forEach((box) => box.checked = !allOn);
        };
        document.querySelectorAll('[data-toggle-row]').forEach((button) =>
            button.addEventListener('click', () => toggle(boxes(`[data-module="${button.dataset.toggleRow}"]`))));
        document.querySelectorAll('[data-toggle-column]').forEach((button) =>
            button.addEventListener('click', () => toggle(boxes(`[data-action="${button.dataset.toggleColumn}"]`))));
    </script>
@endsection
