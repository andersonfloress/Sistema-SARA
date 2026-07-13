<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'IE Santa Rosa') — Sistema SARA</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-transparent.png') }}">

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                fontFamily: {
                    sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                },
                extend: {
                    colors: {
                        crimson: {
                            50:  '#fdf2f4',
                            100: '#fce7eb',
                            200: '#f9d0d8',
                            300: '#f4a8b8',
                            400: '#ec7592',
                            500: '#e04470',
                            600: '#cc2952',
                            700: '#a8183b',
                            800: '#8b1c30',
                            900: '#6b1427',
                            950: '#3d0812',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Scrollbar personalizado */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 99px; }
        /* Sidebar item active glow sutil */
        .nav-active { box-shadow: inset 3px 0 0 #f4a8b8; }
        /* Sidebar: transición de ancho en desktop, de transform en mobile */
        [x-cloak] { display: none !important; }
    </style>

    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased">

<div class="flex h-screen overflow-hidden"
     x-data="{
         open: window.innerWidth >= 768,
         mobile: window.innerWidth < 768,
         init() {
             const mq = window.matchMedia('(max-width: 767px)');
             mq.addEventListener('change', e => {
                 this.mobile = e.matches;
                 if (!e.matches) this.open = true;
                 else this.open = false;
             });
         }
     }">

    {{-- ── Backdrop móvil ──────────────────────────────────────────── --}}
    <div x-show="mobile && open"
         x-cloak
         x-transition:enter="transition-opacity ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 z-40 bg-black/50 md:hidden">
    </div>

    {{-- ══════════════════════════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════════════════════════ --}}
    <aside
        x-cloak
        :class="[
            mobile
                ? (open ? 'translate-x-0' : '-translate-x-full')
                : (open ? 'w-60' : 'w-16')
        ]"
        class="flex-shrink-0 flex flex-col overflow-hidden
               md:relative md:translate-x-0
               fixed inset-y-0 left-0 z-50 w-60
               transition-all duration-300 ease-in-out"
        style="background: linear-gradient(180deg, #3d0812 0%, #5c1020 60%, #6b1427 100%);">

        {{-- Logo / Marca --}}
        <div class="flex items-center gap-3 px-4 py-4 border-b border-white/10 flex-shrink-0">
            <img src="{{ asset('images/logo-transparent.png') }}"
                 alt="IE Santa Rosa"
                 class="flex-shrink-0"
                 style="width:36px; height:36px; object-fit:contain;
                        filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5)) drop-shadow(0 1px 3px rgba(0,0,0,0.4));">

            <div x-show="open || mobile"
                 x-transition:enter="transition-opacity duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="min-w-0">
                <p class="font-bold text-sm text-white leading-tight tracking-wide">IE Santa Rosa</p>
                <p class="text-xs" style="color:#f4a8b8;">Gestión Escolar</p>
            </div>

            {{-- Botón cerrar — solo en móvil --}}
            <button @click="open = false"
                    class="ml-auto md:hidden w-7 h-7 flex items-center justify-center rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition-all flex-shrink-0">
                <i data-lucide="x" style="width:16px;height:16px;"></i>
            </button>
        </div>

        {{-- Navegación --}}
        <nav class="flex-1 py-3 overflow-y-auto space-y-0.5 px-2">
            @php $u = auth()->user(); @endphp

            <a href="{{ route('dashboard') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('dashboard')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="layout-dashboard" class="flex-shrink-0" style="width:18px;height:18px;"></i>
                <span x-show="open || mobile" class="truncate">Dashboard</span>
            </a>

            @if($u->isAdmin())
            <a href="{{ route('usuarios.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('usuarios.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="users" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Usuarios</span>
            </a>
            @endif

            @if($u->isAdmin() || $u->isTeacher())
            <a href="{{ route('alumnos.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('alumnos.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="graduation-cap" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Alumnos</span>
            </a>
            @endif

            @if($u->isAdmin())
            <a href="{{ route('docentes.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('docentes.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="user-check" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Docentes</span>
            </a>
            <a href="{{ route('secciones.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('secciones.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="layers" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Secciones</span>
            </a>
            <a href="{{ route('matricula.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('matricula.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="user-plus" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Matricular Alumnos</span>
            </a>
            <a href="{{ route('promocion.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('promocion.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="arrow-up-circle" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Promoción de Año</span>
            </a>
            <a href="{{ route('anios.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('anios.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="calendar-clock" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Años Escolares</span>
            </a>
            @endif

            <a href="{{ route('calificaciones.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('calificaciones.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="clipboard-list" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Calificaciones</span>
            </a>

            <a href="{{ route('asistencia.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('asistencia.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="check-square" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Asistencia</span>
            </a>

            <a href="{{ route('materiales.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('materiales.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="book-open-check" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Materiales</span>
            </a>

            <a href="{{ route('tareas.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('tareas.index') || request()->routeIs('tareas.show') || request()->routeIs('tareas.create')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="clipboard-check" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Tareas</span>
            </a>

            <a href="{{ route('tareas.libreta') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('tareas.libreta')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="table-2" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Calific. de Tareas</span>
            </a>

            <a href="{{ route('comunicados.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('comunicados.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="megaphone" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Comunicados</span>
            </a>

            <a href="{{ route('horarios.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('horarios.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="clock" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Horarios</span>
            </a>

            <a href="{{ route('calendario.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('calendario.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="calendar" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Calendario</span>
            </a>

            @if($u->isAdmin() || $u->isTeacher())
            <a href="{{ route('reportes.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('reportes.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="bar-chart-2" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Reportes</span>
            </a>
            @endif

            @if($u->isParent())
            <a href="{{ route('padres.index') }}"
               @click="if(mobile) open = false"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('padres.*')
                          ? 'bg-white/15 text-white nav-active'
                          : 'text-crimson-200 hover:bg-white/10 hover:text-white' }}">
                <i data-lucide="users-2" style="width:18px;height:18px;" class="flex-shrink-0"></i>
                <span x-show="open || mobile" class="truncate">Portal Padres</span>
            </a>
            @endif
        </nav>

        {{-- Usuario + Logout --}}
        <div class="border-t border-white/10 p-3 flex-shrink-0">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                     style="background: rgba(255,255,255,0.20);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div x-show="open || mobile" class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs" style="color: #f4a8b8;">{{ auth()->user()->roleLabel() }}</p>
                </div>
            </div>
            <div x-show="open || mobile" class="mt-2.5 flex gap-1.5">
                <a href="{{ route('perfil.edit') }}"
                   class="flex-1 flex items-center justify-center gap-1 py-1.5 px-2 text-xs rounded-lg transition-all"
                   style="color: #f4a8b8; background: rgba(255,255,255,0.08);"
                   onmouseover="this.style.background='rgba(255,255,255,0.15)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                    <i data-lucide="settings" style="width:13px;height:13px;"></i> Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center justify-center gap-1 py-1.5 px-2 text-xs rounded-lg transition-all"
                            style="color: #fca5a5; background: rgba(255,255,255,0.08);"
                            onmouseover="this.style.background='rgba(239,68,68,0.25)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.08)'">
                        <i data-lucide="log-out" style="width:13px;height:13px;"></i> Salir
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ══════════════════════════════════════════════════════════
         CONTENIDO PRINCIPAL
    ══════════════════════════════════════════════════════════ --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between flex-shrink-0 shadow-sm">
            <div class="flex items-center gap-2 min-w-0">
                {{-- Toggle sidebar --}}
                <button @click="open = !open"
                        class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all">
                    <i data-lucide="menu" style="width:18px;height:18px;"></i>
                </button>

                {{-- Botón volver --}}
                @if(!request()->routeIs('dashboard'))
                <button onclick="history.back()"
                        class="w-8 h-8 flex-shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-all"
                        title="Volver">
                    <i data-lucide="arrow-left" style="width:16px;height:16px;"></i>
                </button>
                @endif

                {{-- Breadcrumb --}}
                <div class="flex items-center gap-1.5 min-w-0">
                    <span class="hidden sm:block text-xs text-gray-400 font-medium uppercase tracking-wider flex-shrink-0">IE Santa Rosa</span>
                    <span class="hidden sm:block text-gray-300 flex-shrink-0">/</span>
                    <h1 class="text-sm font-semibold text-gray-800 truncate">@yield('page-title', 'Dashboard')</h1>
                </div>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                {{-- Fecha --}}
                <span class="hidden lg:block text-xs text-gray-400">
                    {{ now()->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </span>

                {{-- Badge de rol --}}
                @php
                    $badgeStyle = match(auth()->user()->role) {
                        'admin'   => 'background:#fce7eb; color:#8b1c30;',
                        'teacher' => 'background:#ede9fe; color:#5b21b6;',
                        'student' => 'background:#dbeafe; color:#1e40af;',
                        'parent'  => 'background:#d1fae5; color:#065f46;',
                        default   => 'background:#f3f4f6; color:#374151;',
                    };
                @endphp
                <span class="hidden sm:inline px-2.5 py-1 text-xs font-semibold rounded-full flex-shrink-0" style="{{ $badgeStyle }}">
                    {{ auth()->user()->roleLabel() }}
                </span>

                {{-- Avatar --}}
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                     style="background: linear-gradient(135deg, #8b1c30, #6b1427);">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
            </div>
        </header>

        {{-- Contenido de página --}}
        <main class="flex-1 overflow-y-auto p-4 md:p-6"
              style="background-color:#fafafa;
                     background-image:
                         radial-gradient(circle at 0% 0%, rgba(139,28,48,0.06), transparent 42%),
                         radial-gradient(circle at 100% 100%, rgba(139,28,48,0.06), transparent 42%),
                         repeating-linear-gradient(135deg, rgba(0,0,0,0.05) 0px, rgba(0,0,0,0.05) 1px, transparent 1px, transparent 14px);
                     background-repeat: no-repeat, no-repeat, repeat;
                     background-size: cover, cover, 20px 20px;">
            @yield('content')
        </main>
    </div>
</div>

{{-- ── Flash messages ─────────────────────────────────────────── --}}
@if(session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'success', title: '¡Éxito!',
            text: '{{ session('success') }}',
            timer: 3000, timerProgressBar: true,
            showConfirmButton: false, toast: true, position: 'top-end',
        });
    });
</script>
@endif

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error', title: 'Error',
            text: '{{ session('error') }}',
            timer: 4000, timerProgressBar: true,
            showConfirmButton: false, toast: true, position: 'top-end',
        });
    });
</script>
@endif

<script>lucide.createIcons();</script>
@stack('scripts')
</body>
</html>
