<!DOCTYPE html>
<html lang="pt-br" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - {{ config('app.name') }}</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
                    colors: {
                        dash: {
                            950: '#0a0a0c',
                            900: '#121217',
                            800: '#1c1c24',
                            700: '#2a2a35'
                        }
                    }
                }
            }
        }
    </script>
    
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #2a2a35; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #3a3a45; }
        
        .glass { background: rgba(18, 18, 23, 0.75); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.05); }
        .btn-grad { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); transition: transform 0.2s; }
        .btn-grad:hover { transform: translateY(-1px); }
    </style>
</head>
<body class="bg-dash-950 text-gray-200 antialiased font-sans">

    <!-- Mobile Sidebar Backdrop -->
    <div x-data="{ open: false }" class="min-h-screen flex flex-col md:flex-row">
        
        <!-- Sidebar -->
        <aside :class="open ? 'translate-x-0' : '-translate-x-full'" class="fixed md:relative md:translate-x-0 w-72 min-h-screen z-50 transition-transform duration-300 ease-in-out glass">
            <div class="p-8">
                <div class="flex items-center space-x-3 mb-10">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-900/40">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                    </div>
                    <div>
                        <h1 class="font-bold text-white leading-tight">Mensageria</h1>
                        <p class="text-[10px] text-blue-400 font-semibold tracking-widest uppercase">TechInteligente</p>
                    </div>
                </div>

                <nav class="space-y-1">
                    <a href="/admin" class="flex items-center space-x-3 p-3 rounded-xl {{ request()->is('admin') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-dash-800 text-gray-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="text-sm font-medium">Dashboard</span>
                    </a>

                    <a href="/admin/plans" class="flex items-center space-x-3 p-3 rounded-xl {{ request()->is('admin/plans*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-dash-800 text-gray-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        <span class="text-sm font-medium">Planos de Venda</span>
                    </a>

                    <a href="/admin/api-keys" class="flex items-center space-x-3 p-3 rounded-xl {{ request()->is('admin/api-keys*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-dash-800 text-gray-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        <span class="text-sm font-medium">Chaves API</span>
                    </a>

                    <a href="/admin/logs" class="flex items-center space-x-3 p-3 rounded-xl {{ request()->is('admin/logs*') ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/20' : 'hover:bg-dash-800 text-gray-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="text-sm font-medium">Relatórios</span>
                    </a>

                    <a href="/admin/financeiro" class="flex items-center space-x-3 p-3 rounded-xl {{ request()->is('admin/financeiro*') ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-900/20' : 'hover:bg-dash-800 text-gray-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span class="text-sm font-medium">Financeiro & Asaas</span>
                    </a>

                    <div class="pt-6 pb-2 text-[10px] font-bold text-gray-600 uppercase tracking-widest px-3">WhatsApp</div>

                    <a href="/admin/whatsapp" class="flex items-center space-x-3 p-3 rounded-xl {{ request()->is('admin/whatsapp*') ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-900/20' : 'hover:bg-dash-800 text-gray-400' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        <span class="text-sm font-medium">Conectar WhatsApp</span>
                    </a>
                </nav>

                <div class="absolute bottom-10 left-8 right-8">
                    <div class="glass p-4 rounded-3xl mb-4 border-dash-700">
                        <div class="flex items-center space-x-3 mb-2">
                            <div class="w-8 h-8 rounded-full bg-dash-700 flex items-center justify-center text-xs font-bold text-blue-400">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                            <div class="truncate">
                                <p class="text-[10px] font-bold text-white truncate">{{ auth()->user()->name }}</p>
                                <p class="text-[8px] text-gray-500 truncate">{{ auth()->user()->email }}</p>
                            </div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center space-x-2 p-3 text-xs font-bold text-red-400 hover:text-red-300 transition hover:bg-red-500/10 rounded-xl">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span>Desconectar</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 min-w-0 md:h-screen md:overflow-y-auto">
            <header class="h-20 flex items-center justify-between px-8 md:px-12 glass border-0 border-b border-white/5 sticky top-0 z-40">
                <div class="flex items-center space-x-4">
                    <button @click="open = !open" class="md:hidden text-gray-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>
                    <h2 class="text-lg font-bold text-white uppercase tracking-wider">@yield('title')</h2>
                </div>
                
                <div class="flex items-center space-x-6">
                    @if(auth()->user()->isAdmin())
                        <div class="bg-blue-500/10 text-blue-400 text-[10px] font-bold px-3 py-1 rounded-full border border-blue-500/20 uppercase">Admin Master</div>
                    @endif
                </div>
            </header>

            <div class="p-8 md:p-12">
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl mb-8 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <span class="text-sm font-medium">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
