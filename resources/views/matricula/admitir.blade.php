@extends('layouts.app')
@section('title', 'Admitir Nuevo Alumno')
@section('page-title', 'Admitir Nuevo Alumno')

@section('content')

<div class="max-w-2xl mx-auto">

    {{-- Encabezado con breadcrumb --}}
    <div class="flex items-center gap-2 mb-5 text-sm text-gray-500">
        <a href="{{ route('matricula.index') }}" class="hover:text-indigo-600 transition">Matrícula</a>
        <span>›</span>
        <span class="text-gray-800 font-medium">Nuevo alumno</span>
    </div>

    @if($errors->any())
    <div class="mb-5 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('matricula.storeAdmision') }}" class="space-y-6">
        @csrf

        {{-- ── Datos del alumno ────────────────────────────────────────── --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 pb-2 border-b border-gray-100">
                Datos del Alumno
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required autofocus
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 @error('name') border-red-400 @enderror"
                           placeholder="Ej: García López, María del Carmen">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="dni" value="{{ old('dni') }}" maxlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="12345678">
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono de Emergencia</label>
                    <input type="text" name="telefono_emergencia" value="{{ old('telefono_emergencia') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="999 888 777">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Condición especial
                        <span class="text-gray-400 font-normal">(NEE, discapacidad, etc. — opcional)</span>
                    </label>
                    <textarea name="condicion_especial" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('condicion_especial') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ── Apoderado ────────────────────────────────────────────────── --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 pb-2 border-b border-gray-100">
                Apoderado
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI del Apoderado</label>
                    <input type="text" name="dni_apoderado" value="{{ old('dni_apoderado') }}" maxlength="8"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="12345678">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del Apoderado</label>
                    <input type="text" name="nombre_apoderado" value="{{ old('nombre_apoderado') }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500"
                           placeholder="García López, Juan">
                </div>
            </div>
        </div>

        {{-- ── Matrícula ────────────────────────────────────────────────── --}}
        @php $sbg = $sectionsByGrade ?? collect(); @endphp
        <div class="bg-white rounded shadow-sm border border-gray-100 p-6"
             x-data="admisionGrado({{ $sbg->toJson() }}, {{ old('grade_destino', 1) }}, {{ old('section_id', 'null') }})">

            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-1 pb-2 border-b border-gray-100">
                Sección de Matrícula
                @if($academicYear)
                <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-medium {{ $academicYear->statusBadgeClass() }}">
                    {{ $academicYear->year }} — {{ $academicYear->statusLabel() }}
                </span>
                @endif
            </h2>
            <p class="text-xs text-gray-400 mb-4">
                Elige el grado al que ingresa el alumno. Para traslados de otro colegio selecciona el grado correspondiente.
                Puedes dejar la sección sin asignar y hacerlo después desde Matrícula.
            </p>

            @if($academicYear && $academicYear->isEnrollmentOpen())
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- Selector de grado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grado de ingreso</label>
                    <select x-model.number="gradoSeleccionado"
                            @change="seccionSeleccionada = null"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        @for($g = 1; $g <= 5; $g++)
                        <option value="{{ $g }}">{{ $g }}° grado</option>
                        @endfor
                    </select>
                </div>

                {{-- Selector de sección (filtrado por grado) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sección</label>
                    <select name="section_id" x-model="seccionSeleccionada"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">— Sin asignar por ahora —</option>
                        <template x-for="sec in seccionesFiltradas" :key="sec.id">
                            <option :value="sec.id"
                                    :disabled="sec.lleno"
                                    x-text="sec.grade + '° ' + sec.name + ' — ' + capitalizar(sec.turno) + ' (' + sec.enrollments_count + '/' + (sec.cupo_maximo ?? '∞') + ')' + (sec.lleno ? ' — LLENO' : '')">
                            </option>
                        </template>
                    </select>
                    <p x-show="seccionesFiltradas.length === 0" class="text-xs text-amber-600 mt-1">
                        No hay secciones creadas para este grado.
                        <a href="{{ route('secciones.index') }}" class="underline">Crear secciones</a>
                    </p>
                </div>
            </div>

            @elseif($academicYear)
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-700">
                La matrícula para {{ $academicYear->year }} no está habilitada.
                El alumno se creará y podrás matricularlo cuando se abra la matrícula.
            </div>
            @else
            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg text-xs text-gray-500">
                No hay años escolares registrados. Crea uno en
                <a href="{{ route('anios.index') }}" class="underline">Años Escolares</a> primero.
            </div>
            @endif
        </div>

        {{-- ── Acceso al sistema ────────────────────────────────────────── --}}
        <div class="bg-white rounded shadow-sm border border-gray-100 p-6">
            <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4 pb-2 border-b border-gray-100">
                Acceso al Sistema
            </h2>
            <p class="text-xs text-gray-400 mb-4">
                Estas credenciales permitirán al alumno y/o su apoderado ingresar al portal.
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 @error('email') border-red-400 @enderror"
                           placeholder="alumno@ejemplo.com">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contraseña *</label>
                    <input type="password" name="password" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">Mínimo 8 caracteres.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
        </div>

        {{-- ── Botones ──────────────────────────────────────────────────── --}}
        <div class="flex gap-3">
            <button type="submit"
                    class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition shadow-sm">
                Admitir y Matricular
            </button>
            <a href="{{ route('matricula.index') }}"
               class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
function admisionGrado(sectionsByGrade, gradoInicial, seccionInicial) {
    return {
        gradoSeleccionado: gradoInicial || 1,
        seccionSeleccionada: seccionInicial || null,
        sectionsByGrade: sectionsByGrade,

        get seccionesFiltradas() {
            return this.sectionsByGrade[this.gradoSeleccionado] || [];
        },

        capitalizar(str) {
            if (!str) return '—';
            return str.charAt(0).toUpperCase() + str.slice(1);
        },
    };
}
</script>
@endpush
