@extends('layouts.app')
@section('title', 'Materiales Educativos')
@section('page-title', 'Materiales Educativos')

@section('content')

{{-- ── Selector de hijo (solo para padres con más de un hijo) ───────── --}}
@include('partials.parent_child_selector', [
    'children'     => $children     ?? collect(),
    'selectedChild'=> $selectedChild ?? null,
])

@php
    $isStudentOrParent = auth()->user()->isStudent() || auth()->user()->isParent();
@endphp

@if($isStudentOrParent && $courses->isNotEmpty())
{{-- ── Chips de cursos (filtrado Alpine.js sin recarga) ─────────────── --}}
<div x-data="materialesFilter()" x-init="init()" class="mb-6">

    {{-- Fila de chips --}}
    <div class="flex flex-wrap gap-2 mb-6">
        <button
            @click="setCourse(null)"
            :class="activeCourse === null
                ? 'bg-[#8b1c30] text-white border-[#8b1c30] shadow-sm'
                : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30] hover:text-[#8b1c30]'"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition-all duration-150 cursor-pointer">
            <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i>
            Todos
            <span :class="activeCourse === null ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-500'"
                  class="ml-1 text-xs px-1.5 py-0.5 rounded-full font-semibold">
                {{ $materials->total() }}
            </span>
        </button>

        @foreach($courses as $c)
        <button
            @click="setCourse({{ $c->id }})"
            :class="activeCourse === {{ $c->id }}
                ? 'bg-[#8b1c30] text-white border-[#8b1c30] shadow-sm'
                : 'bg-white text-gray-600 border-gray-200 hover:border-[#8b1c30] hover:text-[#8b1c30]'"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full border text-sm font-medium transition-all duration-150 cursor-pointer">
            <i data-lucide="book" class="w-3.5 h-3.5"></i>
            {{ $c->name }}
            <span :class="activeCourse === {{ $c->id }} ? 'bg-white/25 text-white' : 'bg-gray-100 text-gray-500'"
                  class="ml-1 text-xs px-1.5 py-0.5 rounded-full font-semibold"
                  x-text="counts[{{ $c->id }}] ?? 0">
            </span>
        </button>
        @endforeach
    </div>

    {{-- Cuadrícula filtrada --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($materials as $m)
        <div class="bg-white rounded shadow-sm border border-gray-100 p-5 flex flex-col transition-all duration-150"
             data-course-id="{{ $m->course_id }}"
             x-show="activeCourse === null || activeCourse === {{ $m->course_id }}"
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start justify-between gap-2 mb-3">
                <div class="w-10 h-10 rounded flex items-center justify-center flex-shrink-0"
                     style="background:#fce7eb;">
                    <i data-lucide="{{ $m->typeIcon() }}" style="width:20px;height:20px;color:#8b1c30;"></i>
                </div>
            </div>
            <span class="inline-block w-fit px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 mb-2">
                {{ $m->typeLabel() }}
            </span>
            <h3 class="font-semibold text-gray-800 mb-1">{{ $m->title }}</h3>
            <p class="text-xs text-gray-500 mb-1">{{ $m->course->name }} — {{ $m->course->section->name }}</p>
            @if($m->description)
            <p class="text-sm text-gray-600 mb-3 flex-1">{{ $m->description }}</p>
            @else
            <div class="flex-1"></div>
            @endif
            <a href="{{ $m->url }}" target="_blank" rel="noopener"
               class="flex items-center gap-2 text-sm font-medium mt-2"
               style="color:#8b1c30;">
                <i data-lucide="external-link" class="w-4 h-4"></i>
                {{ $m->type === 'document' ? 'Descargar' : 'Abrir' }}
            </a>
            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2 text-xs text-gray-400">
                <i data-lucide="user" class="w-3.5 h-3.5"></i>
                <span>{{ $m->teacher->name }}</span>
                <span>·</span>
                <span>{{ $m->created_at->diffForHumans() }}</span>
            </div>
        </div>
        @empty
        <div class="col-span-full bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
            <i data-lucide="book-open-check" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p>No hay materiales publicados todavía.</p>
        </div>
        @endforelse

        {{-- Mensaje "sin resultados" para filtro vacío --}}
        <div class="col-span-full bg-white rounded p-12 text-center text-gray-400 border border-gray-100"
             x-show="{{ $materials->count() }} > 0 && visibleCount === 0"
             x-cloak>
            <i data-lucide="search-x" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
            <p>No hay materiales para este curso todavía.</p>
        </div>
    </div>

    @if($materials->hasPages())
    <div class="mt-6">{{ $materials->links() }}</div>
    @endif
</div>

@else
{{-- ── Vista admin / docente: selector + botón publicar ──────────────── --}}
<div class="flex justify-between items-center mb-6 gap-3 flex-wrap">
    @if($courses->isNotEmpty())
    <form method="GET" class="flex gap-2">
        <select name="course_id" onchange="this.form.submit()"
                class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500">
            <option value="">Todos los cursos</option>
            @foreach($courses as $c)
            <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>
                {{ $c->name }} — {{ $c->section->name }}
            </option>
            @endforeach
        </select>
    </form>
    @else
    <div></div>
    @endif

    @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
    <a href="{{ route('materiales.create') }}"
       class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
        <i data-lucide="plus" class="w-4 h-4"></i> Publicar Material
    </a>
    @endif
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($materials as $m)
    <div class="bg-white rounded shadow-sm border border-gray-100 p-5 flex flex-col">
        <div class="flex items-start justify-between gap-2 mb-3">
            <div class="w-10 h-10 rounded flex items-center justify-center flex-shrink-0"
                 style="background:#fce7eb;">
                <i data-lucide="{{ $m->typeIcon() }}" style="width:20px;height:20px;color:#8b1c30;"></i>
            </div>
            @if(auth()->user()->isAdmin() || auth()->user()->id === $m->teacher_id)
            <form method="POST" action="{{ route('materiales.destroy', $m) }}"
                  onsubmit="return confirmDeleteMat(event, '{{ addslashes($m->title) }}')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
            @endif
        </div>
        <span class="inline-block w-fit px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600 mb-2">
            {{ $m->typeLabel() }}
        </span>
        <h3 class="font-semibold text-gray-800 mb-1">{{ $m->title }}</h3>
        <p class="text-xs text-gray-500 mb-1">{{ $m->course->name }} — {{ $m->course->section->name }}</p>
        @if($m->description)
        <p class="text-sm text-gray-600 mb-3 flex-1">{{ $m->description }}</p>
        @else
        <div class="flex-1"></div>
        @endif
        <a href="{{ $m->url }}" target="_blank" rel="noopener"
           class="flex items-center gap-2 text-sm font-medium mt-2"
           style="color:#8b1c30;">
            <i data-lucide="external-link" class="w-4 h-4"></i>
            {{ $m->type === 'document' ? 'Descargar' : 'Abrir' }}
        </a>
        <div class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2 text-xs text-gray-400">
            <i data-lucide="user" class="w-3.5 h-3.5"></i>
            <span>{{ $m->teacher->name }}</span>
            <span>·</span>
            <span>{{ $m->created_at->diffForHumans() }}</span>
        </div>
    </div>
    @empty
    <div class="col-span-full bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
        <i data-lucide="book-open-check" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
        <p>No hay materiales publicados todavía.</p>
    </div>
    @endforelse
</div>

@if($materials->hasPages())
<div class="mt-6">{{ $materials->links() }}</div>
@endif
@endif

@endsection

@push('scripts')
<script>
function materialesFilter() {
    return {
        activeCourse: null,
        counts: {},
        visibleCount: 0,

        init() {
            // Construir conteo por curso desde las tarjetas del DOM
            const cards = document.querySelectorAll('[data-course-id]');
            cards.forEach(card => {
                const id = parseInt(card.dataset.courseId);
                this.counts[id] = (this.counts[id] || 0) + 1;
            });
            this.visibleCount = cards.length;

            this.$watch('activeCourse', () => {
                const cards = document.querySelectorAll('[data-course-id]');
                this.visibleCount = this.activeCourse === null
                    ? cards.length
                    : [...cards].filter(c => parseInt(c.dataset.courseId) === this.activeCourse).length;
            });
        },

        setCourse(id) {
            this.activeCourse = id;
            // Reinicializar íconos Lucide en las tarjetas recién visibles
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        }
    };
}

function confirmDeleteMat(event, title) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: '¿Eliminar material?', text: `¿Eliminar "${title}"?`, icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
