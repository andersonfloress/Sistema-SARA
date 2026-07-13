@extends('layouts.app')
@section('title', 'Comunicados')
@section('page-title', 'Comunicados')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div></div>
    @if(auth()->user()->isAdmin() || auth()->user()->isTeacher())
    <a href="{{ route('comunicados.create') }}"
       class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
        <i data-lucide="plus" class="w-4 h-4"></i> Nuevo Comunicado
    </a>
    @endif
</div>

<div class="space-y-4">
    @forelse($announcements as $ann)
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-4">
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <h3 class="font-semibold text-gray-800 text-base">{{ $ann->title }}</h3>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $ann->targetRoleClass() }}">
                        {{ $ann->targetRoleLabel() }}
                    </span>
                </div>
                <p class="text-sm text-gray-600 leading-relaxed">{{ $ann->content }}</p>
                <div class="mt-3 flex items-center gap-2 text-xs text-gray-400">
                    <i data-lucide="user" class="w-3.5 h-3.5"></i>
                    <span>{{ $ann->author?->name }}</span>
                    <span>·</span>
                    <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                    <span>{{ $ann->created_at->diffForHumans() }}</span>
                </div>
            </div>
            @if(auth()->user()->isAdmin())
            <form method="POST" action="{{ route('comunicados.destroy', $ann) }}"
                  onsubmit="return confirmDeleteAnn(event, '{{ addslashes($ann->title) }}')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition flex-shrink-0">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </form>
            @endif
        </div>
    </div>
    @empty
    <div class="bg-white rounded p-12 text-center text-gray-400 border border-gray-100">
        <i data-lucide="megaphone" class="w-10 h-10 mx-auto mb-3 opacity-40"></i>
        <p>No hay comunicados publicados.</p>
    </div>
    @endforelse
</div>

@if($announcements->hasPages())
<div class="mt-6">{{ $announcements->links() }}</div>
@endif
@endsection

@push('scripts')
<script>
function confirmDeleteAnn(event, title) {
    event.preventDefault();
    const form = event.target;
    Swal.fire({
        title: '¿Eliminar comunicado?', text: `¿Eliminar "${title}"?`, icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#dc2626', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    }).then(r => { if (r.isConfirmed) form.submit(); });
    return false;
}
</script>
@endpush
