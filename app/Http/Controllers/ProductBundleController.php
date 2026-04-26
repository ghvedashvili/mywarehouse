<?php

namespace App\Http\Controllers;

use App\Models\ProductBundle;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class ProductBundleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff');
    }

    public function index()
    {
        return view('product_bundles.index');
    }

    public function apiIndex()
    {
        $bundles = ProductBundle::withoutGlobalScope('active')
            ->where('status', 'active')
            ->with('products:id,bundle_id,name')
            ->orderBy('name')
            ->get();

        return DataTables::of($bundles)
            ->addColumn('products_list', function ($row) {
                if ($row->products->isEmpty()) return '<span class="text-muted">—</span>';
                $bg = $row->products->count() === 1 ? 'bg-danger' : 'bg-secondary';
                return $row->products->map(fn($p) => '<span class="badge ' . $bg . ' me-1">' . e($p->name) . '</span>')->implode('');
            })
            ->addColumn('action', function ($row) {
                return '
                    <button class="btn btn-xs btn-warning btn-edit"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->name) . '">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button class="btn btn-xs btn-danger btn-delete"
                            data-id="' . $row->id . '">
                        <i class="fa fa-trash"></i>
                    </button>';
            })
            ->rawColumns(['products_list', 'action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255|unique:product_bundles,name']);

        ProductBundle::create(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Bundle შეიქმნა']);
    }

    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255|unique:product_bundles,name,' . $id]);

        $bundle = ProductBundle::withoutGlobalScope('active')->findOrFail($id);
        $bundle->update(['name' => $request->name]);

        return response()->json(['success' => true, 'message' => 'Bundle განახლდა']);
    }

    public function destroy($id)
    {
        $bundle = ProductBundle::withoutGlobalScope('active')->findOrFail($id);
        $bundle->update(['status' => 'deleted']);

        return response()->json(['success' => true, 'message' => 'Bundle წაიშალა']);
    }
}
