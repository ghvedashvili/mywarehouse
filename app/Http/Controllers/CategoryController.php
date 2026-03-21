<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Exports\ExportCategories;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
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
        $this->validate($request, [
            'name' => 'required|string|min:2|unique:categories,name'
        ]);

        Category::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Category Created Successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|string|min:2|unique:categories,name,' . $id,
        ]);

        $category = Category::findOrFail($id);
        $category->update($request->all());

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
                return '<a onclick="editForm('. $category->id .')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                       '<a onclick="deleteData('. $category->id .')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>';
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