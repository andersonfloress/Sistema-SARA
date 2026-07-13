<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Http\Requests\StoreAnnouncementRequest;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $announcements = Announcement::with('author')
            ->where(function ($q) use ($user) {
                $q->where('target_role', 'all')
                  ->orWhere('target_role', $user->role);
            })
            ->latest()
            ->paginate(15);

        return view('comunicados.index', compact('announcements'));
    }

    public function create()
    {
        return view('comunicados.create');
    }

    public function store(StoreAnnouncementRequest $request)
    {
        Announcement::create([
            'title'       => $request->title,
            'content'     => $request->content,
            'author_id'   => auth()->id(),
            'target_role' => $request->target_role,
        ]);

        return redirect()->route('comunicados.index')
                         ->with('success', 'Comunicado publicado correctamente.');
    }

    public function destroy(Announcement $comunicado)
    {
        $comunicado->delete();

        return redirect()->route('comunicados.index')
                         ->with('success', 'Comunicado eliminado correctamente.');
    }
}
