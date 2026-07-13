@extends('layouts.auth')

@section('content')
<div class="w-full max-w-md mx-auto">

    {{-- Card principal --}}
    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">

        {{-- Header de la card --}}
        <div class="px-8 py-10 text-center"
             style="background: linear-gradient(135deg, #3d0812 0%, #6b1427 50%, #8b1c30 100%);">

            {{-- Logo institucional recortado --}}
            <div class="mb-4">
                <img src="{{ asset('images/logo-transparent.png') }}"
                     alt="IE Santa Rosa"
                     style="height:110px; width:auto; object-fit:contain;
                            display:block; margin:0 auto;
                            filter: drop-shadow(0 4px 16px rgba(0,0,0,0.5)) drop-shadow(0 2px 6px rgba(0,0,0,0.3));">
            </div>

            <h1 class="text-xl font-bold text-white tracking-wide mb-0.5">IE Santa Rosa</h1>
            <p class="text-sm" style="color:#f4a8b8;">Sistema SARA</p>

            {{-- Línea decorativa --}}
            <div class="mt-4 flex items-center justify-center gap-3">
                <div class="h-px w-12" style="background:rgba(255,255,255,0.25);"></div>
                <i data-lucide="star" style="width:12px;height:12px;color:rgba(255,255,255,0.4);"></i>
                <div class="h-px w-12" style="background:rgba(255,255,255,0.25);"></div>
            </div>
        </div>

        {{-- Formulario --}}
        <div class="px-8 py-8">
            <h2 class="text-lg font-semibold text-gray-800 mb-6">Iniciar sesión</h2>

            @if($errors->any())
            <div class="mb-5 p-4 rounded-xl text-sm" style="background:#fce7eb; border:1px solid #f4a8b8; color:#8b1c30;">
                @foreach($errors->all() as $error)
                    <p class="flex items-center gap-1.5">
                        <i data-lucide="alert-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
                        {{ $error }}
                    </p>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Correo electrónico
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                            <i data-lucide="mail" style="width:16px;height:16px;color:#9ca3af;"></i>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50
                                      transition-all outline-none
                                      @error('email') border-red-400 bg-red-50 @enderror"
                               onfocus="this.style.borderColor='#cc2952'; this.style.boxShadow='0 0 0 3px rgba(204,41,82,0.12)'"
                               onblur="this.style.borderColor=''; this.style.boxShadow=''"
                               placeholder="correo@santarosa.edu.pe">
                    </div>
                </div>

                {{-- Contraseña --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Contraseña
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-3.5 flex items-center pointer-events-none">
                            <i data-lucide="lock" style="width:16px;height:16px;color:#9ca3af;"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-xl text-sm bg-gray-50 transition-all outline-none"
                               onfocus="this.style.borderColor='#cc2952'; this.style.boxShadow='0 0 0 3px rgba(204,41,82,0.12)'"
                               onblur="this.style.borderColor=''; this.style.boxShadow=''"
                               placeholder="••••••••">
                    </div>
                </div>

                {{-- Recordar --}}
                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember"
                           class="w-4 h-4 rounded border-gray-300"
                           style="accent-color: #8b1c30;">
                    <label for="remember" class="ml-2 text-sm text-gray-500 select-none">Recordar sesión</label>
                </div>

                {{-- Botón --}}
                <button type="submit"
                        class="w-full py-3 px-4 text-white font-semibold rounded-xl text-sm transition-all
                               hover:shadow-lg active:scale-[0.98]"
                        style="background: linear-gradient(135deg, #8b1c30, #6b1427);"
                        onmouseover="this.style.background='linear-gradient(135deg, #a8183b, #8b1c30)'"
                        onmouseout="this.style.background='linear-gradient(135deg, #8b1c30, #6b1427)'">
                    Ingresar al sistema
                </button>
            </form>
        </div>
    </div>

    <p class="text-center text-xs mt-6" style="color:rgba(255,255,255,0.35);">
        &copy; {{ date('Y') }} IE Santa Rosa &middot; Todos los derechos reservados
    </p>
</div>
@endsection
