<?php

namespace App\Http\Controllers;

use App\Models\Courier;
use Illuminate\Http\Request;

class CourierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $couriers = Courier::orderBy('id')->get();
        return view('couriers.index', compact('couriers'));
    }

    public function update(Request $request, $id)
    {
        $courier = Courier::findOrFail($id);

        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'tbilisi_price' => 'required|numeric|min:0',
            'region_price'  => 'required|numeric|min:0',
            'village_price' => 'required|numeric|min:0',
        ]);

        $courier->update($data);

        return response()->json(['success' => true, 'message' => 'კურიერი განახლდა ✅']);
    }
}
