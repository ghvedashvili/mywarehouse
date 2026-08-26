<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StickyNote;

class StickyNoteController extends Controller
{
    public function index()
    {
        return response()->json(
            auth()->user()->stickyNotes()->orderBy('created_at')->get()
        );
    }

    public function store(Request $request)
    {
        $note = auth()->user()->stickyNotes()->create([
            'content' => '',
            'pos_x'   => $request->input('pos_x', 120),
            'pos_y'   => $request->input('pos_y', 120),
        ]);
        return response()->json($note);
    }

    public function update(Request $request, $id)
    {
        $note = auth()->user()->stickyNotes()->findOrFail($id);
        $note->update($request->only(['content', 'pos_x', 'pos_y']));
        return response()->json($note);
    }

    public function destroy($id)
    {
        auth()->user()->stickyNotes()->findOrFail($id)->delete();
        return response()->json(['ok' => true]);
    }
}
