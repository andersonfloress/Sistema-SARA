@extends('layouts.app')
@section('title', 'Editar Perfil Padre/Madre')
@section('page-title', 'Editar Perfil de Padre/Madre')

@section('content')
<div class="max-w-2xl mx-auto bg-white rounded shadow-sm border border-gray-100 p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-1">{{ $padre->name }}</h2>
    <p class="text-sm text-gray-500 mb-5">{{ $padre->email }}</p>

    @if($errors->any())
    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
        @foreach($errors->all() as $error)<p>• {{ $error }}</p>@endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('padres.updateProfile', $padre) }}" class="space-y-6">
        @csrf @method('PUT')

        {{-- Identificación --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Identificación</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">DNI</label>
                    <input type="text" name="dni" value="{{ old('dni', $profile->dni) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                    <p class="text-xs text-gray-400 mt-1">El DNI se usa para vincular automáticamente con los alumnos.</p>
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
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                    <textarea name="direccion" rows="2"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">{{ old('direccion', $profile->direccion) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Datos adicionales --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Datos Adicionales</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ocupación</label>
                    <input type="text" name="ocupacion" value="{{ old('ocupacion', $profile->ocupacion) }}"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Grado de Instrucción</label>
                    <select name="grado_instruccion" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                        <option value="">Seleccionar...</option>
                        <option value="sin_instruccion" @selected(old('grado_instruccion', $profile->grado_instruccion) === 'sin_instruccion')>Sin instrucción</option>
                        <option value="primaria"        @selected(old('grado_instruccion', $profile->grado_instruccion) === 'primaria')>Primaria</option>
                        <option value="secundaria"      @selected(old('grado_instruccion', $profile->grado_instruccion) === 'secundaria')>Secundaria</option>
                        <option value="tecnico"         @selected(old('grado_instruccion', $profile->grado_instruccion) === 'tecnico')>Técnico</option>
                        <option value="universitario"   @selected(old('grado_instruccion', $profile->grado_instruccion) === 'universitario')>Universitario</option>
                        <option value="posgrado"        @selected(old('grado_instruccion', $profile->grado_instruccion) === 'posgrado')>Posgrado</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Hijos vinculados --}}
        @if($padre->children->count())
        <div>
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 pb-2 border-b border-gray-100">Hijos Vinculados</h3>
            <ul class="space-y-1">
                @foreach($padre->children as $hijo)
                <li class="flex items-center gap-2 text-sm text-gray-700">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 flex-shrink-0"></span>
                    <span>{{ $hijo->name }}</span>
                    <span class="text-gray-400 text-xs">({{ $hijo->pivot->parentesco ?? 'parentesco no definido' }})</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="flex gap-3 pt-2">
            <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                Guardar Perfil
            </button>
            <a href="{{ route('usuarios.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
