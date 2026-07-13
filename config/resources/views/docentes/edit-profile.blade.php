@extends('layouts.app')
@section('title', 'Editar Perfil Docente')
@section('page-title', 'Editar Perfil de Docente')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-1">{{ $docente->name }}</h2>
    <p class="text-sm text-gray-500 mb-5">{{ $docente->email }}</p>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('docentes.updateProfile', $docente) }}" class="space-y-6" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- Datos personales --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Datos Personales</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="dni" value="{{ old('dni', $profile->dni) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento', optional($profile->fecha_nacimiento)->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                    <select name="sexo" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="M" @selected(old('sexo', $profile->sexo) === 'M')>Masculino</option>
                        <option value="F" @selected(old('sexo', $profile->sexo) === 'F')>Femenino</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto de Perfil</label>
                    <input type="file" name="foto_perfil" accept="image/*"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm">
                    @if($profile->foto_perfil)
                        <p class="text-xs text-gray-500 mt-1">Foto actual: <a href="{{ Storage::url($profile->foto_perfil) }}" target="_blank" class="text-indigo-600 hover:underline">ver</a></p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Contacto --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Contacto</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="telefono" value="{{ old('telefono', $profile->telefono) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo Alternativo</label>
                    <input type="email" name="correo_alternativo" value="{{ old('correo_alternativo', $profile->correo_alternativo) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                <textarea name="direccion" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('direccion', $profile->direccion) }}</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contacto de Emergencia (Nombre)</label>
                    <input type="text" name="contacto_emergencia_nombre" value="{{ old('contacto_emergencia_nombre', $profile->contacto_emergencia_nombre) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contacto de Emergencia (Teléfono)</label>
                    <input type="text" name="contacto_emergencia_telefono" value="{{ old('contacto_emergencia_telefono', $profile->contacto_emergencia_telefono) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Datos laborales --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Datos Laborales</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código Docente</label>
                    <input type="text" name="codigo_docente" value="{{ old('codigo_docente', $profile->codigo_docente) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                    <input type="text" name="especialidad" value="{{ old('especialidad', $profile->especialidad) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Ingreso</label>
                    <input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', optional($profile->fecha_ingreso)->format('Y-m-d')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condición Laboral</label>
                    <select name="condicion_laboral" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="nombrado" @selected(old('condicion_laboral', $profile->condicion_laboral) === 'nombrado')>Nombrado</option>
                        <option value="contratado" @selected(old('condicion_laboral', $profile->condicion_laboral) === 'contratado')>Contratado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nivel Académico</label>
                    <select name="nivel_academico" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="bachiller" @selected(old('nivel_academico', $profile->nivel_academico) === 'bachiller')>Bachiller</option>
                        <option value="licenciado" @selected(old('nivel_academico', $profile->nivel_academico) === 'licenciado')>Licenciado</option>
                        <option value="magister" @selected(old('nivel_academico', $profile->nivel_academico) === 'magister')>Magíster</option>
                        <option value="doctor" @selected(old('nivel_academico', $profile->nivel_academico) === 'doctor')>Doctor</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">N° de Colegiatura (CPM)</label>
                    <input type="text" name="numero_colegiatura" value="{{ old('numero_colegiatura', $profile->numero_colegiatura) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                    <select name="turno" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="mañana" @selected(old('turno', $profile->turno) === 'mañana')>Mañana</option>
                        <option value="tarde" @selected(old('turno', $profile->turno) === 'tarde')>Tarde</option>
                        <option value="ambos" @selected(old('turno', $profile->turno) === 'ambos')>Ambos</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Carga horaria máxima (h/semana)</label>
                    <input type="number" name="max_horas_semanales" value="{{ old('max_horas_semanales', $profile->max_horas_semanales ?? 30) }}" min="1" max="60"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CV / Título (PDF)</label>
                    <input type="file" name="cv_file" accept="application/pdf"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm">
                    @if($profile->cv_path)
                        <p class="text-xs text-gray-500 mt-1">Documento actual: <a href="{{ Storage::url($profile->cv_path) }}" target="_blank" class="text-indigo-600 hover:underline">ver</a></p>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Guardar Perfil
            </button>
            <a href="{{ route('docentes.show', $docente) }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
