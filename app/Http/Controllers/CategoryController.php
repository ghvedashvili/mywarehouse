<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Exports\ExportCategories;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;
use PDF;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff');
    }

    public function index()
    {
        return view('categories.index');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'  => 'required|string|min:2|unique:categories,name',
            'sizes' => 'nullable|string',
        ]);

        $sizes = '1';
        if ($request->sizes) {
            $sizes = implode(',', array_filter(array_map('trim', explode(',', $request->sizes))));
            if (!$sizes) $sizes = '1';
        }

        Category::create([
            'name'    => $request->name,
            'sizes'   => $sizes,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category Created Successfully'
        ]);
    }

    public function edit($id)
    {
        $category = Category::findOrFail($id);
        // sizes უკვე სტრიქონია — პირდაპირ ვაბრუნებთ
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'  => 'required|string|min:2|unique:categories,name,' . $id,
            'sizes' => 'nullable|string',
        ]);

        $sizes = '1';
        if ($request->sizes) {
            $sizes = implode(',', array_filter(array_map('trim', explode(',', $request->sizes))));
            if (!$sizes) $sizes = '1';
        }

        $category = Category::findOrFail($id);
        $category->update([
            'name'  => $request->name,
            'sizes' => $sizes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category Updated Successfully'
        ]);
    }

    public function destroy($id)
{
    if (!Auth::check()) {
        return response()->json(['message' => 'ავტორიზაცია საჭიროა'], 401);
    }

    if (Auth::user()->role != 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'ამ ქმედების უფლება მხოლოდ ადმინისტრატორს აქვს!'
        ], 403);
    }

    // ვამოწმებთ აქვს თუ არა active პროდუქტები ამ კატეგორიაში
    $products = \App\Models\Product::where('category_id', $id)
        ->select('name', 'product_code')
        ->get();

    if ($products->isNotEmpty()) {
        $list = $products->map(fn($p) => $p->name . ' (' . $p->product_code . ')')->join(', ');
        return response()->json([
            'success' => false,
            'message' => 'კატეგორია გამოიყენება შემდეგ პროდუქტებში (გთხოვთ ჯერ ამ პროდუქტებში შეცვალოთ კატეგორია): ' . $list
        ], 422);
    }

    Category::destroy($id);

    return response()->json([
        'success' => true,
        'message' => 'Category Deleted Successfully'
    ]);
}

  public function apiCategories()
{
    $categories = Category::all();

    return DataTables::of($categories)
        ->addColumn('sizes_display', function($category) {
            if (!$category->sizes) {
                return '<span class="text-muted">-</span>';
            }
            $html = '';
            foreach (explode(',', $category->sizes) as $size) {
                $html .= '<span class="label label-info" style="margin-right:3px;">' . e(trim($size)) . '</span>';
            }
            return $html;
        })
        ->addColumn('status_display', function($category) {
            return '<span class="label label-success">active</span>';
        })
        ->addColumn('action', function($category) {
            if (auth()->user()->role === 'admin') {
                return '<div class="d-flex gap-1">' .
                    '<a onclick="editForm('. $category->id .')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a>' .
                    '<a onclick="deleteData('. $category->id .')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a>' .
                '</div>';
            }
            return '';
        })
        ->rawColumns(['sizes_display', 'status_display', 'action'])
        ->make(true);
}

    public function exportCategoriesAll()
    {
        $categories = Category::all();
        $pdf = PDF::loadView('categories.CategoriesAllPDF', compact('categories'));
        return $pdf->download('categories.pdf');
    }

    public function exportExcel()
    {
        return (new ExportCategories())->download('categories.xlsx');
    }

    public function apiDeletedCategories()
{
    $categories = Category::withoutGlobalScope('active')
        ->where('status', 'deleted')
        ->get();

    return DataTables::of($categories)
        ->addColumn('sizes_display', function($category) {
            if (!$category->sizes) {
                return '<span class="text-muted">-</span>';
            }
            $html = '';
            foreach (explode(',', $category->sizes) as $size) {
                $html .= '<span class="label label-default" style="margin-right:3px;">' . e(trim($size)) . '</span>';
            }
            return $html;
        })
        ->addColumn('status_display', function($category) {
            return '<span class="label label-danger">deleted</span>';
        })
        ->addColumn('action', function($category) {
            if (auth()->user()->role === 'admin') {
                return '<a onclick="restoreData('. $category->id .')" class="btn btn-warning btn-xs"><i class="fa fa-undo"></i> Restore</a>';
            }
            return '';
        })
        ->rawColumns(['sizes_display', 'status_display', 'action'])
        ->make(true);
}

public function restore($id)
{
    if (auth()->user()->role !== 'admin') {
        return response()->json(['success' => false, 'message' => 'უფლება არ გაქვს'], 403);
    }

    $category = Category::withoutGlobalScope('active')->findOrFail($id);
    $category->status = 'active';
    $category->save();

    return response()->json(['success' => true, 'message' => 'Category Restored Successfully']);
}
}