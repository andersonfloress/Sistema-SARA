{{--
    Selector de hijo para el rol padre.
    Variables esperadas:
      $children      — Collection<User>  (todos los hijos del padre)
      $selectedChild — User|null         (hijo actualmente seleccionado)
    Parámetros adicionales de la URL actual se preservan vía request()->except('child_id','page').
--}}
@if(auth()->user()->isParent() && $children->count() > 1)
<div class="mb-6">
    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Viendo información de:</p>
    <div class="flex flex-wrap gap-2">
        @foreach($children as $child)
        @php
            $isActive = $selectedChild && $selectedChild->id === $child->id;
            $params   = array_merge(request()->except(['child_id','page']), ['child_id' => $child->id]);
            $enr      = $child->enrollments()->with('section')
                             ->whereHas('section', fn($q) => $q->where('year', \App\Models\Section::max('year')))
                             ->first();
            $grade    = $enr?->section ? $enr->section->name : '';
        @endphp
        <a href="{{ url()->current() }}?{{ http_build_query($params) }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium border transition-all
                  {{ $isActive
                      ? 'text-white border-transparent shadow-sm'
                      : 'bg-white text-gray-600 border-gray-200 hover:border-crimson-300 hover:text-crimson-700' }}"
           style="{{ $isActive ? 'background:#8b1c30; border-color:#8b1c30;' : '' }}">
            <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                  style="{{ $isActive ? 'background:rgba(255,255,255,0.25);color:#fff;' : 'background:#fce7eb;color:#8b1c30;' }}">
                {{ mb_strtoupper(mb_substr($child->name, 0, 1)) }}
            </span>
            <span>{{ $child->name }}</span>
            @if($grade)
            <span class="text-xs opacity-70">({{ $grade }})</span>
            @endif
        </a>
        @endforeach
    </div>
</div>
@endif
