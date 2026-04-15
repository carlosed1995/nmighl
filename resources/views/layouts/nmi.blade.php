<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NMI Dashboard')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        body { font-family: Inter, sans-serif; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 h-screen flex overflow-hidden antialiased">
    <aside class="w-64 bg-white border-r border-slate-200 flex flex-col z-20 shadow-sm">
        <div class="h-16 flex items-center px-6 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <img src="{{ asset('usapayments.webp') }}" alt="USA Payments Logo" class="h-8 object-contain" />
                <span class="font-bold text-lg tracking-tight text-slate-900">USA Payments</span>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-6 px-4 space-y-1">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('dashboard') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-chart-pie w-5 text-center {{ request()->routeIs('dashboard') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Dashboard</span>
            </a>
            <a href="{{ route('merchant-management') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('merchant-management') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-border-all w-5 text-center {{ request()->routeIs('merchant-management') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Merchant Management</span>
            </a>

            <div class="pt-4 pb-2 px-3">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Payments</p>
            </div>

            <a href="{{ route('online-payments') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('online-payments') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-dollar-sign w-5 text-center {{ request()->routeIs('online-payments') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Online Payments</span>
            </a>
            <a href="{{ route('in-person-payments') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('in-person-payments') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-mobile-screen w-5 text-center {{ request()->routeIs('in-person-payments') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">In-Person Payments</span>
            </a>
            <a href="{{ route('clients-ghl') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('clients-ghl') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-users-gear w-5 text-center {{ request()->routeIs('clients-ghl') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Clients-GHL</span>
            </a>

            <div class="pt-4 pb-2 px-3">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Overview</p>
            </div>

            <a href="{{ route('sales-reps') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('sales-reps') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-briefcase w-5 text-center {{ request()->routeIs('sales-reps') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Sales Reps</span>
            </a>
            <a href="{{ route('reporting') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('reporting') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-chart-line w-5 text-center {{ request()->routeIs('reporting') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Reporting</span>
            </a>
            <a href="{{ route('account-settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg transition-colors {{ request()->routeIs('account-settings') ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}">
                <i class="fa-solid fa-gear w-5 text-center {{ request()->routeIs('account-settings') ? 'text-teal-600' : 'text-slate-400' }}"></i>
                <span class="text-sm">Account Settings</span>
            </a>
        </nav>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shadow-sm">
            <div class="text-sm text-slate-500">NMI API Integration Demo</div>
            <div class="text-sm text-slate-700">{{ auth()->user()->name }}</div>
        </header>

        <main class="flex-1 overflow-y-auto p-6">
            @yield('content')
        </main>
    </div>
</body>
</html>
