<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $after = $request->input('after', 0);

        $messages = ChatMessage::with('user:id,name')
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn($m) => [
                'id'      => $m->id,
                'user_id' => $m->user_id,
                'name'    => $m->user->name ?? '?',
                'body'    => $m->body,
                'time'    => $m->created_at->format('H:i'),
            ]);

        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $request->validate(['body' => 'required|string|max:2000']);

        $msg = ChatMessage::create([
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        $msg->load('user:id,name');

        return response()->json([
            'id'      => $msg->id,
            'user_id' => $msg->user_id,
            'name'    => $msg->user->name,
            'body'    => $msg->body,
            'time'    => $msg->created_at->format('H:i'),
        ]);
    }

    public function recent()
    {
        $messages = ChatMessage::with('user:id,name')
            ->orderByDesc('id')
            ->limit(60)
            ->get()
            ->reverse()
            ->values()
            ->map(fn($m) => [
                'id'      => $m->id,
                'user_id' => $m->user_id,
                'name'    => $m->user->name ?? '?',
                'body'    => $m->body,
                'time'    => $m->created_at->format('H:i'),
            ]);

        return response()->json($messages);
    }
}
