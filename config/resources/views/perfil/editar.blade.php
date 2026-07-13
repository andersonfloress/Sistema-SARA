@extends('layouts.app')
@section('title', 'Editar mi Perfil')
@section('page-title', 'Editar mi Perfil')

@section('content')
@php $user = auth()->user(); $p = $teacherProfile; @endphp

{{-- Cabecera de navegación --}}
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('perfil.edit') }}"
       class="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Volver a mi perfil
    </a>
</div>

@if($errors->any())
<div class="mb-6 p-4 bg-red-50 border border-red-200 rounded text-sm text-red-700">
    @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
</div>
@endif

<form method="POST" action="{{ route('perfil.updateFull') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf @method('PUT')

    {{-- ── Foto de perfil ──────────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-5">
            <i data-lucide="image" class="w-4 h-4 text-indigo-500"></i> Foto de Perfil
        </h3>
        <div class="flex items-center gap-6">
            {{-- Preview actual --}}
            @if($p?->foto_perfil)
                <img src="{{ Storage::url($p->foto_perfil) }}" alt="{{ $user->name }}"
                     class="w-20 h-20 rounded object-cover border border-gray-100 flex-shrink-0">
            @else
                <div class="w-20 h-20 bg-purple-100 rounded flex items-center justify-center
                            text-3xl font-bold text-purple-600 flex-shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
            @endif
            <div class="flex-1">
                <input type="file" name="foto_perfil" accept="image/*"
                       class="w-full text-sm text-gray-500 border border-gray-200 rounded-lg px-3 py-2
                              file:mr-3 file:py-1.5 file:px-4 file:rounded-md file:border-0
                              file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium">
                <p class="text-xs text-gray-400 mt-1.5">JPG, PNG o WEBP. Máximo 2 MB.</p>
            </div>
        </div>
    </div>

    {{-- ── Contacto ─────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-5">
            <i data-lucide="phone" class="w-4 h-4 text-indigo-500"></i> Datos de Contacto
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $p?->telefono) }}"
                       placeholder="Ej: 951 234 567"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Correo Alternativo</label>
                <input type="email" name="correo_alternativo" value="{{ old('correo_alternativo', $p?->correo_alternativo) }}"
                       placeholder="correo@ejemplo.com"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <textarea name="direccion" rows="2" placeholder="Av. ejemplo 123, Puno"
                          class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                                 focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none resize-none">{{ old('direccion', $p?->direccion) }}</textarea>
            </div>
        </div>
    </div>

    {{-- ── Contacto de emergencia ───────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-5">
            <i data-lucide="shield-alert" class="w-4 h-4 text-indigo-500"></i> Contacto de Emergencia
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
                <input type="text" name="contacto_emergencia_nombre"
                       value="{{ old('contacto_emergencia_nombre', $p?->contacto_emergencia_nombre) }}"
                       placeholder="Ej: María López"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de emergencia</label>
                <input type="text" name="contacto_emergencia_telefono"
                       value="{{ old('contacto_emergencia_telefono', $p?->contacto_emergencia_telefono) }}"
                       placeholder="Ej: 952 345 678"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
        </div>
    </div>

    {{-- ── Datos profesionales ──────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-800 flex items-center gap-2 mb-1">
            <i data-lucide="graduation-cap" class="w-4 h-4 text-indigo-500"></i> Datos Profesionales
        </h3>
        <p class="text-xs text-gray-400 mb-5">Puedes mantener actualizados tu especialidad, nivel académico y número de colegiatura.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                <input type="text" name="especialidad" value="{{ old('especialidad', $p?->especialidad) }}"
                       placeholder="Ej: Matemáticas, Comunicación..."
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nivel Académico</label>
                <select name="nivel_academico"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                               focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
                    <option value="">Seleccionar...</option>
                    <option value="bachiller"  @selected(old('nivel_academico', $p?->nivel_academico) === 'bachiller')>Bachiller</option>
                    <option value="licenciado" @selected(old('nivel_academico', $p?->nivel_academico) === 'licenciado')>Licenciado</option>
                    <option value="magister"   @selected(old('nivel_academico', $p?->nivel_academico) === 'magister')>Magíster</option>
                    <option value="doctor"     @selected(old('nivel_academico', $p?->nivel_academico) === 'doctor')>Doctor</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">N° de Colegiatura (CPM)</label>
                <input type="text" name="numero_colegiatura" value="{{ old('numero_colegiatura', $p?->numero_colegiatura) }}"
                       placeholder="Ej: CPM-12345"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm
                              focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">CV / Título (PDF)</label>
                <input type="file" name="cv_file" accept="application/pdf"
                       class="w-full text-sm text-gray-500 border border-gray-200 rounded-lg px-2 py-2
                              file:mr-3 file:py-1.5 file:px-4 file:rounded-md file:border-0
                              file:bg-indigo-50 file:text-indigo-700 file:text-sm file:font-medium">
                @if($p?->cv_path)
                <p class="text-xs text-gray-400 mt-1">
                    Documento actual:
                    <a href="{{ Storage::url($p->cv_path) }}" target="_blank" class="text-indigo-500 hover:underline">ver</a>
                    — subir uno nuevo lo reemplazará.
                </p>
                @else
                <p class="text-xs text-gray-400 mt-1">PDF. Máximo 4 MB.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Datos gestionados por la institución (solo lectura) ─────────── --}}
    <div class="bg-gray-50 rounded border border-gray-200 p-6">
        <div class="flex items-center gap-2 mb-1">
            <i data-lucide="lock" class="w-4 h-4 text-gray-400"></i>
            <h3 class="font-semibold text-gray-500 text-sm">Datos gestionados por la institución</h3>
        </div>
        <p class="text-xs text-gray-400 mb-4">Estos campos solo puede modificarlos la administración. Si hay un error, solicita la corrección al administrador.</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
            @foreach([
                'DNI'               => $p?->dni,
                'Código docente'    => $p?->codigo_docente,
                'Fecha de ingreso'  => optional($p?->fecha_ingreso)->format('d/m/Y'),
                'Condición laboral' => $p?->condicion_laboral ? ucfirst($p->condicion_laboral) : null,
                'Turno'             => $p?->turno ? ucfirst($p->turno) : null,
                'Carga máx.'        => $p?->max_horas_semanales ? $p->max_horas_semanales.' h/sem' : null,
            ] as $label => $value)
            <div>
                <dt class="text-xs text-gray-400">{{ $label }}</dt>
                <dd class="font-medium text-gray-500 mt-0.5">{{ $value ?? '—' }}</dd>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ── Acciones ─────────────────────────────────────────────────────── --}}
    <div class="flex gap-3 pb-2">
        <button type="submit"
                class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
            Guardar cambios
        </button>
        <a href="{{ route('perfil.edit') }}"
           class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
            Cancelar
        </a>
    </div>
</form>
@endsection
