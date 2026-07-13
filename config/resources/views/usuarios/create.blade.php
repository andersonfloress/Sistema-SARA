@extends('layouts.app')
@section('title', 'Nuevo Usuario')
@section('page-title', 'Nuevo Usuario')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded shadow-sm border border-gray-100 p-6"
     x-data="{ role: '{{ old('role', '') }}' }">

    <h2 class="text-lg font-semibold text-gray-800 mb-5">Crear Usuario</h2>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-6">
        @csrf

        {{-- ── Paso 1: Rol ──────────────────────────────────────────────── --}}
        <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
            <label class="block text-sm font-semibold text-indigo-800 mb-2">1. Selecciona el rol *</label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach(['student'=>['label'=>'Alumno','icon'=>'🎓'],'parent'=>['label'=>'Padre/Madre','icon'=>'👨‍👩‍👧'],'teacher'=>['label'=>'Docente','icon'=>'👨‍🏫'],'admin'=>['label'=>'Admin','icon'=>'🔧']] as $r => $info)
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="{{ $r }}" x-model="role" class="sr-only" {{ old('role') === $r ? 'checked' : '' }}>
                    <div :class="role === '{{ $r }}' ? 'border-indigo-500 bg-white shadow-sm ring-2 ring-indigo-300' : 'border-gray-200 bg-white hover:border-gray-300'"
                         class="border-2 rounded-lg p-3 text-center transition">
                        <div class="text-xl mb-1">{{ $info['icon'] }}</div>
                        <div class="text-xs font-medium text-gray-700">{{ $info['label'] }}</div>
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        {{-- ── Paso 2: Datos de cuenta (siempre visibles) ──────────────── --}}
        <div x-show="role !== ''" x-cloak>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Datos de Acceso</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 @error('email') border-red-400 @enderror">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                    <input type="password" name="password" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- ── Perfil ALUMNO ────────────────────────────────────────────── --}}
        <div x-show="role === 'student'" x-cloak class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide pb-2 border-b border-gray-100">Perfil del Alumno</h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Código Estudiante</label>
                    <input type="text" name="codigo_estudiante" value="{{ old('codigo_estudiante') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="dni" value="{{ old('dni') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sexo</label>
                    <select name="sexo" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="M" {{ old('sexo') === 'M' ? 'selected' : '' }}>Masculino</option>
                        <option value="F" {{ old('sexo') === 'F' ? 'selected' : '' }}>Femenino</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nacionalidad</label>
                    <input type="text" name="nacionalidad" value="{{ old('nacionalidad') }}" placeholder="Ej: Peruana"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo de Sangre</label>
                    <select name="tipo_sangre" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        @foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $ts)
                            <option value="{{ $ts }}" {{ old('tipo_sangre') === $ts ? 'selected' : '' }}>{{ $ts }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grado</label>
                    <select name="grado" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        @for($g = 1; $g <= 5; $g++)
                            <option value="{{ $g }}" {{ old('grado') == $g ? 'selected' : '' }}>{{ $g }}° grado</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Turno</label>
                    <select name="turno" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="Mañana" {{ old('turno') === 'Mañana' ? 'selected' : '' }}>Mañana</option>
                        <option value="Tarde"  {{ old('turno') === 'Tarde'  ? 'selected' : '' }}>Tarde</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Año de Ingreso</label>
                    <input type="number" name="anio_ingreso" min="1990" max="2100" value="{{ old('anio_ingreso', date('Y')) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de Emergencia</label>
                    <input type="text" name="telefono_emergencia" value="{{ old('telefono_emergencia') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Condición Especial <span class="text-gray-400 font-normal">(NEE, discapacidad, etc.)</span></label>
                    <textarea name="condicion_especial" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('condicion_especial') }}</textarea>
                </div>
            </div>

            <h4 class="text-sm font-semibold text-gray-600 mt-2">Apoderado</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI del Apoderado</label>
                    <input type="text" name="dni_apoderado" value="{{ old('dni_apoderado') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Vincula automáticamente al padre registrado con ese DNI.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Apoderado</label>
                    <input type="text" name="nombre_apoderado" value="{{ old('nombre_apoderado') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- ── Perfil PADRE/MADRE ───────────────────────────────────────── --}}
        <div x-show="role === 'parent'" x-cloak class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide pb-2 border-b border-gray-100">Perfil del Padre/Madre</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="p_dni" value="{{ old('p_dni') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">El DNI permite vincular automáticamente con los alumnos.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="p_telefono" value="{{ old('p_telefono') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ocupación</label>
                    <input type="text" name="ocupacion" value="{{ old('ocupacion') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grado de Instrucción</label>
                    <select name="grado_instruccion" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="sin_instruccion" {{ old('grado_instruccion') === 'sin_instruccion' ? 'selected' : '' }}>Sin instrucción</option>
                        <option value="primaria"        {{ old('grado_instruccion') === 'primaria'        ? 'selected' : '' }}>Primaria</option>
                        <option value="secundaria"      {{ old('grado_instruccion') === 'secundaria'      ? 'selected' : '' }}>Secundaria</option>
                        <option value="tecnico"         {{ old('grado_instruccion') === 'tecnico'         ? 'selected' : '' }}>Técnico</option>
                        <option value="universitario"   {{ old('grado_instruccion') === 'universitario'   ? 'selected' : '' }}>Universitario</option>
                        <option value="posgrado"        {{ old('grado_instruccion') === 'posgrado'        ? 'selected' : '' }}>Posgrado</option>
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea name="p_direccion" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('p_direccion') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Perfil DOCENTE ───────────────────────────────────────────── --}}
        <div x-show="role === 'teacher'" x-cloak class="space-y-4">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide pb-2 border-b border-gray-100">Perfil del Docente</h3>
            <p class="text-xs text-gray-400">Puedes completar el perfil completo del docente después desde su ficha.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="t_dni" value="{{ old('t_dni') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
                    <input type="text" name="especialidad" value="{{ old('especialidad') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                    <input type="text" name="t_telefono" value="{{ old('t_telefono') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- ── Botones ──────────────────────────────────────────────────── --}}
        <div class="flex gap-3 pt-2" x-show="role !== ''" x-cloak>
            <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Crear Usuario
            </button>
            <a href="{{ route('usuarios.index') }}"
               class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
