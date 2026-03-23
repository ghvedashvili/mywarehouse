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
            'sizes' => 'nullable|string'
        ]);

        // ზომებს ვასუფთავებთ — "S , M,  L" -> "S,M,L"
        $sizes = null;
        if ($request->sizes) {
            $sizes = implode(',', array_filter(array_map('trim', explode(',', $request->sizes))));
        }

        Category::create([
    'name'    => $request->name,
    'sizes'   => $sizes,
    'user_id' => Auth::id(), // ავტომატურად ჩაიწერება მიმდინარე მომხმარებელი
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
            'sizes' => 'nullable|string'
        ]);

        $sizes = null;
        if ($request->sizes) {
            $sizes = implode(',', array_filter(array_map('trim', explode(',', $request->sizes))));
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
        ->addColumn('action', function($category) {
            if (auth()->user()->role === 'admin') {
                return '<a onclick="editForm('. $category->id .')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                       '<a onclick="deleteData('. $category->id .')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>';
            }
            return '';
        })
        ->rawColumns(['sizes_display', 'action'])
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
}