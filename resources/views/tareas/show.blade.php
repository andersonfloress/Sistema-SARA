@extends('layouts.app')
@section('title', $tarea->title)
@section('page-title', 'Detalle de Tarea')

@section('content')
@php
    $user    = auth()->user();
    $expired = $tarea->isExpired();
@endphp

<div class="max-w-4xl mx-auto space-y-5">

    {{-- ── Encabezado de la tarea ────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div class="flex items-start gap-4 min-w-0">
                <div class="w-12 h-12 rounded flex items-center justify-center flex-shrink-0"
                     style="background:#fce7eb;">
                    <i data-lucide="clipboard-check" style="width:22px;height:22px;color:#8b1c30;"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-800 mb-1">{{ $tarea->title }}</h2>
                    <p class="text-sm text-gray-500 mb-2">
                        {{ $tarea->course->name }} — {{ $tarea->course->section->name }}
                        · Publicado por <span class="font-medium">{{ $tarea->teacher->name }}</span>
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full
                                     {{ $expired ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                            <i data-lucide="{{ $expired ? 'clock-x' : 'clock' }}" class="w-3 h-3"></i>
                            Límite: {{ $tarea->deadline->format('d/m/Y H:i') }}
                            {{ $expired ? '(vencida)' : '' }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                            {{ $tarea->max_attempts }} intento(s) permitido(s)
                        </span>
                    </div>
                </div>
            </div>

            @if($user->isTeacher() || $user->isAdmin())
            <form method="POST" action="{{ route('tareas.destroy', $tarea) }}"
                  onsubmit="return confirm('¿Eliminar esta tarea y todas sus entregas?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-red-500 text-sm border border-red-200 rounded-lg hover:bg-red-50 transition">
                    <i data-lucide="trash-2" class="w-4 h-4"></i> Eliminar
                </button>
            </form>
            @endif
        </div>

        @if($tarea->description)
        <div class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-100">
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $tarea->description }}</p>
        </div>
        @endif

        @if($tarea->file_path)
        <div class="mt-4">
            <a href="{{ Storage::url($tarea->file_path) }}" target="_blank"
               class="inline-flex items-center gap-2 text-sm font-medium px-4 py-2 rounded-lg border border-[#8b1c30] text-[#8b1c30] hover:bg-[#fce7eb] transition">
                <i data-lucide="download" class="w-4 h-4"></i>
                Descargar archivo adjunto del docente
            </a>
        </div>
        @endif
    </div>

    {{-- ── Vista Alumno: mis entregas + formulario ───────────────── --}}
    @if($user->isStudent())
    @php
        $usedAttempts = $mySubmissions->count();
        $canSubmit    = !$expired && $usedAttempts < $tarea->max_attempts;
    @endphp

    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="upload" class="w-4 h-4 text-[#8b1c30]"></i>
            Mis entregas
            <span class="text-sm font-normal text-gray-400">({{ $usedAttempts }}/{{ $tarea->max_attempts }} intentos usados)</span>
        </h3>

        @if($mySubmissions->isEmpty())
        <p class="text-sm text-gray-400 mb-4">Aún no has enviado ningún archivo.</p>
        @else
        <div class="space-y-2 mb-4">
            @foreach($mySubmissions as $sub)
            <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                <div class="flex items-center gap-3 min-w-0">
                    <i data-lucide="file" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-700 truncate">{{ $sub->original_name }}</p>
                        <p class="text-xs text-gray-400">Intento {{ $sub->attempt }} · {{ $sub->submitted_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 flex-shrink-0">
                    @if($sub->grade !== null)
                    <span class="text-sm font-bold text-[#8b1c30]">{{ number_format($sub->grade, 1) }}/20</span>
                    @endif
                    @if($sub->teacher_note)
                    <span class="text-xs text-gray-500 italic hidden sm:block max-w-xs truncate">"{{ $sub->teacher_note }}"</span>
                    @endif
                    <a href="{{ Storage::url($sub->file_path) }}" target="_blank"
                       class="p-1.5 rounded-lg text-[#8b1c30] hover:bg-[#fce7eb] transition">
                        <i data-lucide="download" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if($canSubmit)
        <form method="POST" action="{{ route('tareas.submit', $tarea) }}" enctype="multipart/form-data">
            @csrf
            @error('file')
            <p class="text-sm text-red-600 mb-2">{{ $message }}</p>
            @enderror
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <label class="flex-1 flex items-center gap-3 border-2 border-dashed border-gray-200 rounded-lg px-4 py-3 cursor-pointer hover:border-[#8b1c30] transition-colors group">
                    <i data-lucide="paperclip" class="w-5 h-5 text-gray-300 group-hover:text-[#8b1c30] transition-colors flex-shrink-0"></i>
                    <span class="text-sm text-gray-500 group-hover:text-gray-700 truncate" id="submit-file-name">
                        Seleccionar archivo (PDF, Word, Excel, PPT)
                    </span>
                    <input type="file" name="file" class="hidden" required
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                           onchange="document.getElementById('submit-file-name').textContent = this.files[0]?.name ?? 'Seleccionar archivo'">
                </label>
                <button type="submit"
                        class="flex-shrink-0 flex items-center gap-2 px-5 py-2.5 bg-[#8b1c30] text-white rounded-lg text-sm font-medium hover:bg-[#6b1427] transition">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    Entregar
                    @if($usedAttempts > 0)(intento {{ $usedAttempts + 1 }})@endif
                </button>
            </div>
        </form>
        @elseif($expired && $usedAttempts === 0)
        <div class="flex items-center gap-2 p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100">
            <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
            La fecha límite venció sin que enviaras tu tarea.
        </div>
        @elseif($usedAttempts >= $tarea->max_attempts)
        <div class="flex items-center gap-2 p-3 bg-blue-50 text-blue-700 text-sm rounded-lg border border-blue-100">
            <i data-lucide="check-circle-2" class="w-4 h-4 flex-shrink-0"></i>
            Ya usaste todos los intentos permitidos.
        </div>
        @endif
    </div>
    @endif

    {{-- ── Vista Padre: entregas de sus hijos ────────────────────── --}}
    @if($user->isParent() && $childrenSubmissions->isNotEmpty())
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="users" class="w-4 h-4 text-[#8b1c30]"></i>
            Entregas de mis hijos
        </h3>
        @foreach($childrenSubmissions as $studentId => $subs)
        @php $firstSub = $subs->first(); @endphp
        <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 mb-2">{{ $firstSub->student->name }}</p>
            <div class="space-y-2">
                @foreach($subs as $sub)
                <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                    <div class="flex items-center gap-2 min-w-0">
                        <i data-lucide="file" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ $sub->original_name }}</p>
                            <p class="text-xs text-gray-400">Intento {{ $sub->attempt }} · {{ $sub->submitted_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($sub->grade !== null)
                        <span class="text-sm font-bold text-[#8b1c30]">{{ number_format($sub->grade, 1) }}/20</span>
                        @endif
                        <a href="{{ Storage::url($sub->file_path) }}" target="_blank"
                           class="p-1.5 rounded-lg text-[#8b1c30] hover:bg-[#fce7eb] transition">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @elseif($user->isParent() && $childrenSubmissions->isEmpty())
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 p-3 bg-amber-50 text-amber-700 text-sm rounded-lg border border-amber-100">
            <i data-lucide="alert-triangle" class="w-4 h-4 flex-shrink-0"></i>
            Tu hijo/a aún no ha enviado su tarea.
            @if(!$expired) Fecha límite: {{ $tarea->deadline->format('d/m/Y H:i') }} @endif
        </div>
    </div>
    @endif

    {{-- ── Vista Docente / Admin: panel de entregas + calificación ─── --}}
    @if(($user->isTeacher() || $user->isAdmin()) && $allSubmissions->isNotEmpty())
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i data-lucide="list-checks" class="w-4 h-4 text-[#8b1c30]"></i>
            Entregas recibidas
            <span class="text-sm font-normal text-gray-400">({{ $allSubmissions->count() }} alumno(s))</span>
        </h3>

        <div class="space-y-4">
            @foreach($allSubmissions as $studentId => $subs)
            @php $latestSub = $subs->first(); @endphp
            <details class="group border border-gray-100 rounded-lg overflow-hidden">
                <summary class="flex items-center justify-between gap-3 px-4 py-3 bg-gray-50 cursor-pointer hover:bg-gray-100 transition list-none">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-7 h-7 rounded-full bg-[#8b1c30] text-white text-xs font-bold flex items-center justify-center flex-shrink-0">
                            {{ strtoupper(substr($latestSub->student->name, 0, 1)) }}
                        </div>
                        <span class="text-sm font-medium text-gray-700 truncate">{{ $latestSub->student->name }}</span>
                        <span class="text-xs text-gray-400">{{ $subs->count() }} entrega(s)</span>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        @if($latestSub->grade !== null)
                        <span class="text-sm font-bold text-[#8b1c30]">{{ number_format($latestSub->grade, 1) }}/20</span>
                        @else
                        <span class="text-xs text-gray-400">Sin nota</span>
                        @endif
                        <i data-lucide="chevron-down" class="w-4 h-4 text-gray-400 group-open:rotate-180 transition-transform"></i>
                    </div>
                </summary>

                <div class="p-4 space-y-3">
                    {{-- Listado de intentos --}}
                    @foreach($subs as $sub)
                    <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-lg border border-gray-100">
                        <div class="flex items-center gap-2 min-w-0">
                            <i data-lucide="file" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-700 truncate">{{ $sub->original_name }}</p>
                                <p class="text-xs text-gray-400">Intento {{ $sub->attempt }} · {{ $sub->submitted_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <a href="{{ Storage::url($sub->file_path) }}" target="_blank"
                           class="p-1.5 rounded-lg text-[#8b1c30] hover:bg-[#fce7eb] transition flex-shrink-0">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </a>
                    </div>
                    @endforeach

                    {{-- Formulario de calificación en el último intento --}}
                    <form method="POST"
                          action="{{ route('tareas.grade', [$tarea, $latestSub]) }}"
                          class="flex flex-wrap gap-3 items-end pt-1">
                        @csrf @method('PATCH')
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nota (0–20)</label>
                            <input type="number" name="grade" step="0.1" min="0" max="20"
                                   value="{{ $latestSub->grade ?? '' }}"
                                   class="w-24 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#8b1c30] focus:outline-none"
                                   placeholder="—">
                        </div>
                        <div class="flex-1 min-w-48">
                            <label class="block text-xs text-gray-500 mb-1">Comentario (opcional)</label>
                            <input type="text" name="teacher_note" maxlength="300"
                                   value="{{ $latestSub->teacher_note ?? '' }}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#8b1c30] focus:outline-none"
                                   placeholder="Ej: Buen desarrollo, falta justificación">
                        </div>
                        <button type="submit"
                                class="px-4 py-2 bg-[#8b1c30] text-white rounded-lg text-sm font-medium hover:bg-[#6b1427] transition flex-shrink-0">
                            Guardar
                        </button>
                    </form>
                </div>
            </details>
            @endforeach
        </div>
    </div>
    @elseif(($user->isTeacher() || $user->isAdmin()) && $allSubmissions->isEmpty())
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <div class="flex items-center gap-2 p-3 bg-gray-50 text-gray-400 text-sm rounded-lg">
            <i data-lucide="inbox" class="w-4 h-4 flex-shrink-0"></i>
            Aún no hay entregas para esta tarea.
        </div>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
// Reinicializar íconos dentro de <details> al abrirlos
document.querySelectorAll('details').forEach(d => {
    d.addEventListener('toggle', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
});
</script>
@endpush
