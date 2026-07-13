@extends('layouts.app')
@section('title', 'Editar Perfil Alumno')
@section('page-title', 'Editar Perfil de Alumno')

@section('content')
<div class="max-w-3xl mx-auto bg-white rounded shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-1">{{ $alumno->name }}</h2>
    <p class="text-sm text-gray-500 mb-5">{{ $alumno->email }}</p>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('alumnos.updateProfile', $alumno) }}" class="space-y-6" enctype="multipart/form-data">
        @csrf @method('PUT')

        {{-- Identificación --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Identificación</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código Estudiante</label>
                    <input type="text" name="codigo_estudiante" value="{{ old('codigo_estudiante', $profile->codigo_estudiante) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="dni" value="{{ old('dni', $profile->dni) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año de Ingreso</label>
                    <input type="number" name="anio_ingreso" min="1990" max="2100"
                           value="{{ old('anio_ingreso', $profile->anio_ingreso) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- Datos personales --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Datos Personales</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento"
                           value="{{ old('fecha_nacimiento', $profile->fecha_nacimiento?->format('Y-m-d')) }}"
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nacionalidad</label>
                    <input type="text" name="nacionalidad" value="{{ old('nacionalidad', $profile->nacionalidad) }}"
                           placeholder="Ej: Peruana"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Sangre</label>
                    <select name="tipo_sangre" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts)
                            <option value="{{ $ts }}" @selected(old('tipo_sangre', $profile->tipo_sangre) === $ts)>{{ $ts }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto de Perfil</label>
                    <input type="file" name="foto_perfil" accept="image/jpeg,image/png,image/webp"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-sm">
                    @if($profile->foto_perfil)
                        <p class="text-xs text-gray-500 mt-1">Foto actual:
                            <a href="{{ Storage::url($profile->foto_perfil) }}" target="_blank" class="text-indigo-600 hover:underline">ver</a>
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Académico --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Datos Académicos</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grado</label>
                    <input type="number" name="grado" min="1" max="5"
                           value="{{ old('grado', $profile->grado) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                    <select name="turno" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="Mañana" @selected(old('turno', $profile->turno) === 'Mañana')>Mañana</option>
                        <option value="Tarde"  @selected(old('turno', $profile->turno) === 'Tarde')>Tarde</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condición Especial <span class="text-gray-400 font-normal">(NEE, discapacidad, etc.)</span></label>
                    <textarea name="condicion_especial" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('condicion_especial', $profile->condicion_especial) }}</textarea>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de Emergencia</label>
                    <input type="text" name="telefono_emergencia" value="{{ old('telefono_emergencia', $profile->telefono_emergencia) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea name="direccion" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('direccion', $profile->direccion) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Apoderado --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">
                Apoderado / Contacto de Emergencia
                <span class="text-xs text-gray-400 font-normal ml-2">El DNI del apoderado vincula automáticamente al padre registrado en el sistema</span>
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI del Apoderado</label>
                    <input type="text" name="dni_apoderado" value="{{ old('dni_apoderado', $profile->dni_apoderado) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Apoderado</label>
                    <input type="text" name="nombre_apoderado" value="{{ old('nombre_apoderado', $profile->nombre_apoderado) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parentesco</label>
                    <select name="parentesco" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        @php
                            $parentescoActual = optional($alumno->parents->first())->pivot->parentesco ?? null;
                        @endphp
                        @foreach(['padre'=>'Padre','madre'=>'Madre','tutor'=>'Tutor','tutora'=>'Tutora','abuelo'=>'Abuelo','abuela'=>'Abuela','tio'=>'Tío','tia'=>'Tía','otro'=>'Otro'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('parentesco', $parentescoActual) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Guardar Perfil
            </button>
            <a href="{{ route('alumnos.show', $alumno) }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
