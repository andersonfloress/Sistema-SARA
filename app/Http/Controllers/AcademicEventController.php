<?php

namespace App\Http\Controllers;

use App\Models\AcademicEvent;
use App\Http\Requests\StoreAcademicEventRequest;
use Carbon\Carbon;

class AcademicEventController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();

        // Mes seleccionado (default = mes actual)
        try {
            $month = $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->month)->startOfMonth()
                : Carbon::now()->startOfMonth();
        } catch (\Exception $e) {
            $month = Carbon::now()->startOfMonth();
        }

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd   = $month->copy()->endOfMonth();

        // Solo los eventos del mes seleccionado visibles para este rol
        $events = AcademicEvent::with('author')
            ->where(function ($q) use ($user) {
                $q->where('target_role', 'all')
                  ->orWhere('target_role', $user->role);
            })
            ->whereBetween('event_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('event_date')
            ->get();

        // Agrupar por día del mes para la grilla
        $eventsByDay = $events->groupBy(fn($e) => $e->event_date->day);

        return view('calendario.index', compact('events', 'eventsByDay', 'month', 'monthStart', 'monthEnd'));
    }

    public function create()
    {
        return view('calendario.create');
    }

    public function store(StoreAcademicEventRequest $request)
    {
        AcademicEvent::create([
            'title'       => $request->title,
            'description' => $request->description,
            'event_date'  => $request->event_date,
            'target_role' => $request->target_role,
            'author_id'   => auth()->id(),
        ]);

        return redirect()->route('calendario.index')
                         ->with('success', 'Evento publicado correctamente.');
    }

    public function destroy(AcademicEvent $evento)
    {
        $evento->delete();

        return redirect()->route('calendario.index')
                         ->with('success', 'Evento eliminado correctamente.');
    }
}
