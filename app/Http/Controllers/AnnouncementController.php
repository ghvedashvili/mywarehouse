<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin')->except(['latest', 'markRead']);
    }

    // Admin: შეტყობინების შექმნა
    public function store(Request $request)
    {
        $request->validate(['message' => 'required|string|max:500']);

        Announcement::create([
            'message'    => $request->message,
            'created_by' => auth()->id(),
            'is_active'  => true,
        ]);

        return response()->json(['success' => true]);
    }

    // Admin: გამორთვა/ჩართვა
    public function toggle($id)
    {
        $ann = Announcement::findOrFail($id);
        $ann->update(['is_active' => !$ann->is_active]);
        return response()->json(['success' => true, 'is_active' => $ann->is_active]);
    }

    // Admin: წაშლა
    public function destroy($id)
    {
        Announcement::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ყველა user: ბოლო წაუკითხავი
    public function latest()
    {
        $ann = Announcement::latestUnreadFor(auth()->id());
        $data = $ann ? [
            'id'      => $ann->id,
            'message' => $ann->message,
            'author'  => $ann->author?->name,
        ] : null;
        return response()->json(['announcement' => $data])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    // ყველა user: წაკითხულად მონიშვნა
    public function markRead($id)
    {
        AnnouncementRead::firstOrCreate([
            'announcement_id' => $id,
            'user_id'         => auth()->id(),
        ]);
        return response()->json(['success' => true]);
    }

    // Admin: სია
    public function index()
    {
        $list = Announcement::with('author')->latest()->take(20)->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'message'    => $a->message,
                'is_active'  => $a->is_active,
                'created_at' => $a->created_at->format('d.m.Y H:i'),
                'author'     => $a->author?->name,
                'reads'      => $a->reads()->count(),
            ]);
        return response()->json($list);
    }
}
