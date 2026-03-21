<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
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
        $category = Category::orderBy('name', 'ASC')->pluck('name', 'id');
        return view('products.index', compact('category'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'        => 'required|string',
            'Price_geo'   => 'required',
            'Price_usa'   => 'required',
            'category_id' => 'required|exists:categories,id',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $input = $request->all();
        $input['price_geo'] = $request->Price_geo;
        $input['price_usa'] = $request->Price_usa;
        $input['image'] = null;

        if ($request->hasFile('image')) {
            $filename = Str::slug($input['name'], '-') . '.' . $request->image->getClientOriginalExtension();
            $input['image'] = '/upload/products/' . $filename;
            $request->image->move(public_path('/upload/products/'), $filename);
        }

        Product::create($input);

        return response()->json([
            'success' => true,
            'message' => 'Product Created Successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $product->image_url = $product->image ? url($product->image) : null;
        return response()->json($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'        => 'required|string',
            'Price_geo'   => 'required',
            'Price_usa'   => 'required',
            'category_id' => 'required|exists:categories,id',
        ]);

        $product = Product::findOrFail($id);
        $input = $request->all();
        
        $input['price_geo'] = $request->Price_geo;
        $input['price_usa'] = $request->Price_usa;
        $input['image'] = $product->image;

        if ($request->hasFile('image')) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }
            $filename = Str::slug($input['name'], '-') . '.' . $request->image->getClientOriginalExtension();
            $input['image'] = '/upload/products/' . $filename;
            $request->image->move(public_path('/upload/products/'), $filename);
        }

        $product->update($input);

        return response()->json([
            'success' => true,
            'message' => 'Product Updated Successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }

        Product::destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'Product Deleted Successfully'
        ]);
    }

    /**
     * Yajra DataTables API
     */
    public function apiProducts()
    {
        $products = Product::with('category')->get();

        return Datatables::of($products)
            ->addColumn('category_name', function ($product) {
                return $product->category ? $product->category->name : 'N/A';
            })
            ->addColumn('show_photo', function ($product) {
                if (!$product->image) {
                    return 'No Image';
                }
                return '<img class="rounded-square" width="50" height="50" src="' . url($product->image) . '" alt="">';
            })
            ->addColumn('price_geo_formatted', function($product) {
                return number_format($product->price_geo, 2) . ' GEL';
            })
            ->addColumn('action', function ($product) {
                return '<a onclick="editForm(' . $product->id . ')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                       '<a onclick="deleteData(' . $product->id . ')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>';
            })
            ->rawColumns(['show_photo', 'action'])
            ->make(true);
    }
}