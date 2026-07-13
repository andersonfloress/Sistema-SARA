@extends('layouts.app')
@section('title', 'Usuarios')
@section('page-title', 'Gestión de Usuarios')

@section('content')
@php
    $roleTab    = request('role', '');
    $searchVal  = request('search', '');
    $totalAll   = $counts->sum();
@endphp

{{-- ── Tarjetas de conteo ────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
    @foreach([
        ['role'=>'',        'label'=>'Total',    'icon'=>'users',           'bg'=>'bg-gray-50',   'text'=>'text-gray-700',   'ring'=>'ring-gray-200',   'count'=>$totalAll],
        ['role'=>'student', 'label'=>'Alumnos',  'icon'=>'graduation-cap',  'bg'=>'bg-blue-50',   'text'=>'text-blue-700',   'ring'=>'ring-blue-200',   'count'=>$counts->get('student',0)],
        ['role'=>'teacher', 'label'=>'Docentes', 'icon'=>'book-open',       'bg'=>'bg-purple-50', 'text'=>'text-purple-700', 'ring'=>'ring-purple-200', 'count'=>$counts->get('teacher',0)],
        ['role'=>'parent',  'label'=>'Padres',   'icon'=>'heart-handshake', 'bg'=>'bg-green-50',  'text'=>'text-green-700',  'ring'=>'ring-green-200',  'count'=>$counts->get('parent',0)],
    ] as $card)
    <a href="{{ route('usuarios.index', array_merge(request()->except('page'), ['role'=>$card['role'], 'search'=>$searchVal])) }}"
       class="flex items-center gap-3 px-4 py-3 rounded border {{ $card['bg'] }} {{ $roleTab === $card['role'] ? 'ring-2 '.$card['ring'].' shadow-sm' : 'border-gray-100 hover:shadow-sm' }} transition">
        <div class="p-2 rounded-lg {{ $card['bg'] }} {{ $card['text'] }}">
            <i data-lucide="{{ $card['icon'] }}" class="w-5 h-5"></i>
        </div>
        <div>
            <p class="text-xs text-gray-500">{{ $card['label'] }}</p>
            <p class="text-xl font-bold {{ $card['text'] }}">{{ $card['count'] }}</p>
        </div>
    </a>
    @endforeach
</div>

{{-- ── Panel principal ───────────────────────────────────────────────── --}}
<div class="bg-white rounded shadow-sm border border-gray-100">

    {{-- Barra de herramientas --}}
    <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">

        {{-- Pestañas de rol --}}
        <div class="flex gap-1 flex-wrap">
            @foreach([
                [''        , 'Todos',    'text-gray-600',   'bg-gray-100'],
                ['student' , 'Alumnos',  'text-blue-700',   'bg-blue-100'],
                ['teacher' , 'Docentes', 'text-purple-700', 'bg-purple-100'],
                ['parent'  , 'Padres',   'text-green-700',  'bg-green-100'],
                ['admin'   , 'Admin',    'text-red-700',    'bg-red-100'],
            ] as [$r, $label, $textColor, $activeBg])
            <a href="{{ route('usuarios.index', array_merge(request()->except('page','role'), ['role'=>$r, 'search'=>$searchVal])) }}"
               class="px-3 py-1.5 rounded-lg text-sm font-medium transition
                      {{ $roleTab === $r ? "$activeBg $textColor" : 'text-gray-500 hover:bg-gray-50' }}">
                {{ $label }}
                @if($r === '' )
                    <span class="ml-1 text-xs opacity-60">{{ $totalAll }}</span>
                @elseif($counts->get($r, 0) > 0)
                    <span class="ml-1 text-xs opacity-60">{{ $counts->get($r, 0) }}</span>
                @endif
            </a>
            @endforeach
        </div>

        {{-- Buscador + botón nuevo --}}
        <div class="flex gap-2 items-center">
            <form method="GET" id="searchForm" class="relative">
                <input type="hidden" name="role" value="{{ $roleTab }}">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                <input type="text" name="search" id="searchInput" value="{{ $searchVal }}"
                       placeholder="Buscar por nombre o correo…"
                       class="pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 w-64 transition">
            </form>
            <a href="{{ route('usuarios.create') }}"
               class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition whitespace-nowrap">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Nuevo Usuario
            </a>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase tracking-wide">
                    <th class="px-5 py-3 font-medium">Usuario</th>
                    <th class="px-5 py-3 font-medium">Correo</th>
                    <th class="px-5 py-3 font-medium">Rol</th>
                    <th class="px-5 py-3 font-medium">DNI / Teléfono</th>
                    <th class="px-5 py-3 font-medium">Registrado</th>
                    <th class="px-5 py-3 font-medium text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($users as $u)
                @php
                    [$avatarBg, $avatarText, $roleBg, $roleText, $roleLabel] = match($u->role) {
                        'admin'   => ['bg-red-100',    'text-red-700',    'bg-red-50',    'text-red-700',    'Admin'],
                        'teacher' => ['bg-purple-100', 'text-purple-700', 'bg-purple-50', 'text-purple-700', 'Docente'],
                        'student' => ['bg-blue-100',   'text-blue-700',   'bg-blue-50',   'text-blue-700',   'Alumno'],
                        'parent'  => ['bg-green-100',  'text-green-700',  'bg-green-50',  'text-green-700',  'Padre/Madre'],
                        default   => ['bg-gray-100',   'text-gray-700',   'bg-gray-50',   'text-gray-700',   $u->role],
                    };
                    $profile = match($u->role) {
                        'student' => $u->studentProfile,
                        'teacher' => $u->teacherProfile,
                        'parent'  => $u->parentProfile,
                        default   => null,
                    };
                    $dni      = $profile?->dni ?? '—';
                    $telefono = $profile?->telefono ?? '—';
                    $initial  = mb_strtoupper(mb_substr($u->name, 0, 1));
                @endphp
                <tr class="hover:bg-gray-50/60 transition">
                    {{-- Avatar + nombre --}}
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full {{ $avatarBg }} {{ $avatarText }} flex items-center justify-center font-semibold text-sm flex-shrink-0">
                                {{ $initial }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate">{{ $u->name }}</p>
                                <p class="text-xs text-gray-400 truncate sm:hidden">{{ $u->email }}</p>
                            </div>
                        </div>
                    </td>

                    {{-- Correo --}}
                    <td class="px-5 py-3 text-gray-500 hidden sm:table-cell">{{ $u->email }}</td>

                    {{-- Rol --}}
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleBg }} {{ $roleText }}">
                            {{ $roleLabel }}
                        </span>
                    </td>

                    {{-- DNI / Teléfono --}}
                    <td class="px-5 py-3 text-gray-500 hidden md:table-cell">
                        @if($profile)
                        <span class="block">DNI: {{ $dni }}</span>
                        <span class="block text-xs text-gray-400">Tel: {{ $telefono }}</span>
                        @else
                        <span class="text-gray-300">—</span>
                        @endif
                    </td>

                    {{-- Fecha --}}
                    <td class="px-5 py-3 text-gray-400 text-xs hidden lg:table-cell whitespace-nowrap">
                        {{ $u->created_at->format('d/m/Y') }}
                    </td>

                    {{-- Acciones --}}
                    <td class="px-5 py-3">
                        <div class="flex justify-end items-center gap-1">

                            {{-- Editar perfil según rol --}}
                            @if($u->role === 'student')
                            <a href="{{ route('alumnos.editProfile', $u) }}"
                               class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Editar perfil alumno">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            </a>
                            @elseif($u->role === 'teacher')
                            <a href="{{ route('docentes.editProfile', $u) }}"
                               class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition" title="Editar perfil docente">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            </a>
                            @elseif($u->role === 'parent')
                            <a href="{{ route('padres.editProfile', $u) }}"
                               class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition" title="Editar perfil padre">
                                <i data-lucide="clipboard-list" class="w-4 h-4"></i>
                            </a>
                            @endif

                            {{-- Editar cuenta --}}
                            <a href="{{ route('usuarios.edit', $u) }}"
                               class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Editar cuenta">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>

                            {{-- Ver ficha (alumnos y docentes) --}}
                            @if($u->role === 'student')
                            <a href="{{ route('alumnos.show', $u) }}"
                               class="p-1.5 text-gray-500 hover:bg-gray-100 rounded-lg transition" title="Ver ficha">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            @elseif($u->role === 'teacher')
                            <a href="{{ route('docentes.show', $u) }}"
                               class="p-1.5 text-gray-500 hover:bg-gray-100 rounded-lg transition" title="Ver ficha">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </a>
                            @endif

                            {{-- Eliminar --}}
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('usuarios.destroy', $u) }}"
                                  onsubmit="return confirmDelete(event, '{{ addslashes($u->name) }}')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                        <i data-lucide="users" class="w-10 h-10 mx-auto mb-3 opacity-30"></i>
                        <p class="font-medium text-gray-500">No se encontraron usuarios</p>
                        @if($searchVal)
                        <p class="text-sm mt-1">Intenta con otro término de búsqueda.</p>
                        <a href="{{ route('usuarios.index', ['role'=>$roleTab]) }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Limpiar búsqueda</a>
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación + info --}}
    @if($users->hasPages() || $users->total() > 0)
    <div class="px-5 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <p class="text-xs text-gray-400">
            Mostrando {{ $users->firstItem() }}–{{ $users->lastItem() }} de {{ $users->total() }} usuarios
        </p>
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Búsqueda automática con debounce
(function () {
    const input = document.getElementById('searchInput');
    if (!input) return;
    let timer;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            document.getElementById('searchForm').submit();
        }, 400);
    });
})();

function confirmDelete(event, name) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: '¿Eliminar usuario?',
        text: `Se eliminará "${name}" y todos sus datos. Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
