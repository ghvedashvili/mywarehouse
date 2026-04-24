<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class BrandController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff,sale_operator');
    }

    public function index()
    {
        return view('brands.index');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|min:2|unique:brands,name',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $data = ['name' => $request->name];

        if ($request->hasFile('logo')) {
            $data['logo'] = $this->uploadLogo($request);
        }

        Brand::create($data);

        return response()->json(['success' => true, 'message' => 'Brand Created Successfully']);
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        return response()->json($brand);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|string|min:2|unique:brands,name,' . $id,
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $brand = Brand::findOrFail($id);
        $data = ['name' => $request->name];

        if ($request->hasFile('logo')) {
            $this->deleteLogo($brand->logo);
            $data['logo'] = $this->uploadLogo($request);
        }

        $brand->update($data);

        return response()->json(['success' => true, 'message' => 'Brand Updated Successfully']);
    }

    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'ამ ქმედების უფლება მხოლოდ ადმინისტრატორს აქვს!'], 403);
        }

        $products = \App\Models\Product::where('brand_id', $id)->select('name', 'product_code')->get();

        if ($products->isNotEmpty()) {
            $list = $products->map(fn($p) => $p->name . ' (' . $p->product_code . ')')->join(', ');
            return response()->json([
                'success' => false,
                'message' => 'ბრენდი გამოიყენება შემდეგ პროდუქტებში (გთხოვთ ჯერ ამ პროდუქტებში შეცვალოთ ბრენდი): ' . $list
            ], 422);
        }

        $brand = Brand::findOrFail($id);
        $brand->delete();

        return response()->json(['success' => true, 'message' => 'Brand Deleted Successfully']);
    }

    public function apiBrands()
    {
        $brands = Brand::all();

        return DataTables::of($brands)
            ->addColumn('logo_display', fn($b) => $this->logoHtml($b))
            ->addColumn('status_display', fn($b) => '<span class="label label-success">active</span>')
            ->addColumn('action', function ($b) {
                $role = auth()->user()->role;
                if ($role === 'admin') {
                    return '<div class="d-flex gap-1">' .
                        '<a onclick="editForm(' . $b->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a>' .
                        '<a onclick="deleteData(' . $b->id . ')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a>' .
                        '</div>';
                }
                if ($role === 'sale_operator') {
                    return '<a onclick="editForm(' . $b->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a>';
                }
                return '';
            })
            ->rawColumns(['logo_display', 'status_display', 'action'])
            ->make(true);
    }

    public function apiDeletedBrands()
    {
        $brands = Brand::withoutGlobalScope('active')->where('status', 'deleted')->get();

        return DataTables::of($brands)
            ->addColumn('logo_display', fn($b) => $this->logoHtml($b))
            ->addColumn('status_display', fn($b) => '<span class="label label-danger">deleted</span>')
            ->addColumn('action', function ($b) {
                if (auth()->user()->role === 'admin') {
                    return '<a onclick="restoreData(' . $b->id . ')" class="btn btn-warning btn-xs"><i class="fa fa-undo"></i> Restore</a>';
                }
                return '';
            })
            ->rawColumns(['logo_display', 'status_display', 'action'])
            ->make(true);
    }

    public function restore($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'უფლება არ გაქვს'], 403);
        }

        $brand = Brand::withoutGlobalScope('active')->findOrFail($id);
        $brand->status = 'active';
        $brand->save();

        return response()->json(['success' => true, 'message' => 'Brand Restored Successfully']);
    }

    private function uploadLogo(Request $request): string
    {
        $filename = 'brand-' . time() . '.' . $request->logo->getClientOriginalExtension();
        if (config('filesystems.default') === 's3') {
            Storage::disk('s3')->putFileAs('brands', $request->file('logo'), $filename, 'public');
            return 'brands/' . $filename;
        }
        $request->logo->move(public_path('/upload/brands/'), $filename);
        return '/upload/brands/' . $filename;
    }

    private function deleteLogo(?string $logo): void
    {
        if (!$logo) return;
        if (str_starts_with($logo, '/')) {
            $path = public_path($logo);
            if (file_exists($path)) unlink($path);
        } else {
            Storage::disk(config('filesystems.default') === 's3' ? 's3' : 'public')->delete($logo);
        }
    }

    private function logoHtml(Brand $b): string
    {
        if (!$b->logo_url) return '<span class="text-muted">-</span>';
        return '<img src="' . $b->logo_url . '" style="height:38px;width:auto;max-width:80px;object-fit:contain;border-radius:4px;">';
    }
}
