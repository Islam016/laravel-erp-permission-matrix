<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Settings') · {{ config('app.name', 'ERP Permission Matrix') }}</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    @auth
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
                <a href="{{ url('/') }}" class="font-semibold text-slate-900">{{ config('app.name', 'ERP Permission Matrix') }}</a>
                <nav class="flex items-center gap-4 text-sm">
                    @can('roles.manage')
                        <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'text-indigo-600 font-medium' : 'text-slate-600 hover:text-slate-900' }}">Roles</a>
                    @endcan
                    @canany(['users.view', 'users.manage'])
                        <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'text-indigo-600 font-medium' : 'text-slate-600 hover:text-slate-900' }}">Users</a>
                    @endcanany
                    <span class="text-slate-400">|</span>
                    <span class="text-slate-500">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="text-slate-600 hover:text-slate-900">Sign out</button>
                    </form>
                </nav>
            </div>
        </header>
    @endauth

    <main class="mx-auto max-w-6xl px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
