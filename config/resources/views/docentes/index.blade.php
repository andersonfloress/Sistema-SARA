@extends('layouts.app')
@section('title', 'Docentes')
@section('page-title', 'Docentes')

@section('content')
<div class="bg-white rounded shadow-sm border border-gray-100">
    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-semibold text-gray-800">Lista de Docentes ({{ $teachers->total() }})</h2>
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Nombre, correo o DNI…"
                   class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 w-52">

            <select name="especialidad"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 bg-white">
                <option value="">Todas las especialidades</option>
                @foreach($especialidades as $esp)
                    <option value="{{ $esp }}" @selected(request('especialidad') === $esp)>{{ $esp }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-3 py-2 bg-indigo-600 text-white rounded-lg text-sm hover:bg-indigo-700 transition flex items-center gap-1">
                <i data-lucide="search" class="w-4 h-4"></i> Buscar
            </button>

            @if(request()->hasAny(['search', 'especialidad']))
            <a href="{{ route('docentes.index') }}"
               class="px-3 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200 transition flex items-center gap-1">
                <i data-lucide="x" class="w-4 h-4"></i> Limpiar
            </a>
            @endif
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-6 py-3">Docente</th>
                    <th class="px-6 py-3">Correo</th>
                    <th class="px-6 py-3">Especialidad</th>
                    <th class="px-6 py-3">Cursos</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($teachers as $t)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-purple-100 rounded-full flex items-center justify-center text-sm font-bold text-purple-700 flex-shrink-0">
                                {{ strtoupper(substr($t->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-800">{{ $t->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-gray-500">{{ $t->email }}</td>
                    <td class="px-6 py-3 text-gray-500">{{ $t->teacherProfile?->especialidad ?? '—' }}</td>
                    <td class="px-6 py-3">
                        <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">
                            {{ $t->courses->count() }} cursos
                        </span>
                    </td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('docentes.show', $t) }}"
                           class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition inline-flex" title="Ver perfil">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400">No se encontraron docentes.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($teachers->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $teachers->links() }}</div>
    @endif
</div>
@endsection
