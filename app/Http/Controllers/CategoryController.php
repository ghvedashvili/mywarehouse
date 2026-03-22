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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('categories.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // 1. ვალიდაცია
    $this->validate($request, [
        'name' => 'required|string|min:2|unique:categories,name',
        'sizes' => 'nullable|string' // ზომები სავალდებულო არაა, მაგრამ თუ არის - ტექსტია
    ]);

    // 2. ვინახავთ კატეგორიას
    $category = Category::create($request->all());

    // 3. თუ მომხმარებელმა ზომები მიუთითა
    if ($request->sizes) {
        // ტექსტს (მაგ: "S, M, L") ვშლით მძიმეებით მასივად ["S", " M", " L"]
        $sizesArray = explode(',', $request->sizes);

        foreach ($sizesArray as $sizeName) {
            $trimmedSize = trim($sizeName); // ვაშორებთ ზედმეტ სფეისებს (" M" -> "M")
            
            if ($trimmedSize != "") {
                // ვიყენებთ მოდელებს შორის კავშირს ჩასაწერად
                $category->sizes()->create([
                    'name' => $trimmedSize
                ]);
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Category and Sizes Created Successfully'
    ]);
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
{
    // ვიღებთ კატეგორიას თავისი ზომებით
    $category = Category::with('sizes')->findOrFail($id);
    
    // ზომების მასივს ვაქცევთ მძიმით გამოყოფილ ტექსტად (მაგ: "S, M, L")
    $category->sizes_list = $category->sizes->pluck('name')->implode(', ');

    return response()->json($category);
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $this->validate($request, [
        'name' => 'required|string|min:2|unique:categories,name,' . $id,
        'sizes' => 'nullable|string'
    ]);

    $category = Category::findOrFail($id);
    $category->update($request->all());

    // 1. ვშლით ძველ ზომებს, რომ ახლებით ჩავანაცვლოთ
    $category->sizes()->delete();

    // 2. ვამატებთ ახალ ზომებს (თუ შეყვანილია)
    if ($request->sizes) {
        $sizesArray = explode(',', $request->sizes);
        foreach ($sizesArray as $sizeName) {
            $trimmedSize = trim($sizeName);
            if ($trimmedSize != "") {
                $category->sizes()->create([
                    'name' => $trimmedSize
                ]);
            }
        }
    }

    return response()->json([
        'success' => true,
        'message' => 'Category Updated Successfully'
    ]);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        if (!Auth::check()) {
        return response()->json(['message' => 'ავტორიზაცია საჭიროა'], 401);
    }

    // ვამოწმებთ როლს (დააკვირდი, ბაზაში 'admin' წერია თუ 'Admin' - დიდ ასოს აქვს მნიშვნელობა!)
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

    /**
     * Yajra DataTables API
     */
    public function apiCategories()
    {
        $categories = Category::all();

        return DataTables::of($categories)
            ->addColumn('action', function($category) {
                // ვამოწმებთ, არის თუ არა მომხმარებელი ადმინი
                if (auth()->user()->role === 'admin') {
                    return '<a onclick="editForm('. $category->id .')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                        '<a onclick="deleteData('. $category->id .')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>';
                }
                
                // თუ არ არის ადმინი, ვაბრუნებთ ცარიელ მნიშვნელობას
                return '';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    /**
     * Export to PDF
     */
    public function exportCategoriesAll()
    {
        $categories = Category::all();
        $pdf = PDF::loadView('categories.CategoriesAllPDF', compact('categories'));
        return $pdf->download('categories.pdf');
    }

    /**
     * Export to Excel
     */
    public function exportExcel()
    {
        return (new ExportCategories())->download('categories.xlsx');
    }
}