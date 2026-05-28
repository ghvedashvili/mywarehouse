<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $motivational = null;
        if (session()->pull('show_motivational')) {
            $motivational = $this->pickMotivationalText();
        }
        return view('home', compact('motivational'));
    }

    public function markMotivationalSeen(Request $request)
    {
        $index  = (int) $request->input('index');
        $userId = auth()->id();
        $key    = "motivational_seen_{$userId}";
        $seen   = Cache::get($key, []);

        if (!in_array($index, $seen)) {
            $seen[] = $index;
            Cache::put($key, $seen, now()->addYears(2));
        }

        return response()->json(['success' => true]);
    }

    private function pickMotivationalText(): ?array
    {
        $texts  = config('motivational.texts', []);
        if (empty($texts)) return null;

        $userId = auth()->id();
        $key    = "motivational_seen_{$userId}";
        $seen   = Cache::get($key, []);

        $allIndices    = array_keys($texts);
        $unseenIndices = array_values(array_diff($allIndices, $seen));

        if (empty($unseenIndices)) return null;

        $index = $unseenIndices[array_rand($unseenIndices)];
        return ['index' => $index, 'text' => $texts[$index]];
    }
}
