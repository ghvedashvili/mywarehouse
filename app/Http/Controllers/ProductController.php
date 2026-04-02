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
        'product_code' => 'required|string|unique:products,product_code',
        'name'         => 'required|string',
        'Price_geo'    => 'required',
        'category_id'  => 'required|exists:categories,id',
        'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'sizes'        => 'nullable|array',
    ]);

    $input = $request->all();

    $input['price_geo'] = $request->Price_geo;
    $input['product_status'] = $request->has('product_status') ? 1 : 0;
    $input['in_warehouse']   = $request->has('in_warehouse') ? 1 : 0;

    // 3. ზომების დამუშავება (მასივი -> სტრიქონი)
    $input['sizes'] = $request->has('sizes') ? implode(',', $request->sizes) : null;

    // 4. სურათის ატვირთვა product_code-ის სახელით
    if ($request->hasFile('image')) {
        // ვიყენებთ product_code-ს და დროს უნიკალურობისთვის
        $filename = Str::slug($request->product_code, '-') . '-' . time() . '.' . $request->image->getClientOriginalExtension();
        
        $input['image'] = '/upload/products/' . $filename;
        $request->image->move(public_path('/upload/products/'), $filename);
    } else {
        $input['image'] = null;
    }

    // 5. შენახვა ბაზაში
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
    // ვიყენებთ with('category')-ს, თუ მომავალში დაგჭირდებათ კატეგორიის მონაცემებიც
    $product = Product::findOrFail($id);

    // ვამატებთ სრულ URL-ს სურათისთვის, რომ JS-ში მარტივად გამოაჩინოთ
    $product->image_url = $product->image ? url($product->image) : null;

    // ვინაიდან ბაზაში sizes ინახება როგორც "S,M,L", 
    // აქ არაფრის შეცვლა არ გჭირდებათ, რადგან JS-ში უკვე გავაკეთეთ .split(',')
    
    return response()->json($product);
}

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $this->validate($request, [
        'product_code' => 'required|string|unique:products,product_code,' . $id,
        'name'         => 'required|string',
        'Price_geo'    => 'required',
        'category_id'  => 'required|exists:categories,id',
        'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'sizes'        => 'nullable|array',
    ]);

    $input = $request->all();

    $input['price_geo'] = $request->Price_geo;
    $input['product_status'] = $request->has('product_status') ? 1 : 0;
    $input['in_warehouse']   = $request->has('in_warehouse') ? 1 : 0;

    // ზომების დამუშავება
    $input['sizes'] = $request->has('sizes') ? implode(',', $request->sizes) : null;

    // სურათის დამუშავება
    if ($request->hasFile('image')) {
        // 1. თუ ძველი სურათი არსებობს, ვშლით ფიზიკურ ფაილს
        if ($product->image && file_exists(public_path($product->image))) {
            unlink(public_path($product->image));
        }

        // 2. ახალი სახელი product_code-ის მიხედვით + დროის შტამპი (ქეშირებისთვის)
        $filename = Str::slug($request->product_code, '-') . '-' . time() . '.' . $request->image->getClientOriginalExtension();
        
        // 3. შენახვა
        $input['image'] = '/upload/products/' . $filename;
        $request->image->move(public_path('/upload/products/'), $filename);
    } else {
        // თუ სურათი არ იცვლება, ვინარჩუნებთ ძველ გზას
        $input['image'] = $product->image;
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

    // შევამოწმოთ — გამოიყენება თუ არა ეს პროდუქტი რომელიმე ორდერში
    $usedInOrders = \App\Models\Product_Order::withoutGlobalScope('active')
        ->where('product_id', $id)
        ->exists();

    if ($usedInOrders) {
        // წაშლა შეუძლებელია, მხოლოდ Inactive-ად ვნიშნავთ
        $product->update(['product_status' => 0]);

        return response()->json([
            'success' => false,
            'cant_delete' => true,
            'message' => 'პროდუქტი გამოიყენება ორდერ(ებ)ში და ვერ წაიშლება. პროდუქტი Inactive-ად დაინიშნა.'
        ], 422);
    }

    $product->delete(); // იძახებს Model-ის override delete()-ს → status='deleted'

    return response()->json([
        'success' => true,
        'message' => 'Product Deleted Successfully'
    ]);
}

public function restore($id)
{
    $product = Product::withoutGlobalScope('active')
    ->where('status', 'deleted')
    ->findOrFail($id);
    $product->update([
        'status' => 'active',
        'product_status' => 1
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Product Restored Successfully'
    ]);
}

public function apiDeletedProducts(Request $request)
{
   $products = Product::withoutGlobalScope('active')
    ->where('status', 'deleted')
    ->with('category')
    ->select('products.*');

    return \DataTables::of($products->get())
        ->addColumn('category_name', function ($product) {
            return $product->category ? $product->category->name : '<span class="label label-default">N/A</span>';
        })
        ->addColumn('show_photo', function ($product) {
            if (!$product->image) return '<span class="label label-default">No Image</span>';
            return '<img src="'.url($product->image).'" class="img-thumbnail img-zoom-trigger" style="width:50px; height:50px; object-fit:cover; cursor:pointer;">';
        })
        ->addColumn('format_sizes', function ($product) {
            if (!$product->sizes) return '<span class="text-muted">-</span>';
            $html = '';
            foreach (explode(',', $product->sizes) as $size) {
                $html .= '<span class="label label-info" style="margin-right:2px;">' . e(trim($size)) . '</span>';
            }
            return $html;
        })
        ->addColumn('status_stock', function ($product) {
            return '<span class="label label-danger">Deleted</span>';
        })
        ->addColumn('action', function ($product) {
            return '<center>
                <a onclick="restoreData(' . $product->id . ')" class="btn btn-success btn-xs">
                    <i class="glyphicon glyphicon-refresh"></i> Restore
                </a></center>';
        })
        ->rawColumns(['category_name', 'show_photo', 'status_stock', 'format_sizes', 'action'])
        ->make(true);
}

    /**
     * Yajra DataTables API
     */
   public function apiProducts(Request $request)
{
    // ვიყენებთ select('products.*') და eager loading-ს კატეგორიისთვის
    $products = \App\Models\Product::with('category')->select('products.*');

    // --- ფილტრაციის ლოგიკა ---

    // 1. ფილტრაცია კატეგორიით
    if ($request->has('category_id') && $request->category_id != "") {
        $products->where('category_id', $request->category_id);
    }

    // 2. ფილტრაცია სტატუსით (Active/Inactive)
    // ვიყენებთ strlen-ს, რადგან "0" შეიძლება ცარიელად ჩაითვალოს
    if ($request->has('product_status') && strlen($request->product_status) > 0) {
        $products->where('product_status', $request->product_status);
    }

    // 3. ფილტრაცია საწყობის მიხედვით (In Stock/Out of Stock)
    if ($request->has('in_warehouse') && strlen($request->in_warehouse) > 0) {
        $products->where('in_warehouse', $request->in_warehouse);
    }

    // --- DataTable-ის ფორმირება ---

    return \DataTables::of($products->get())
        // კატეგორიის სახელი
        ->addColumn('category_name', function ($product) {
            return $product->category ? $product->category->name : '<span class="label label-default">N/A</span>';
        })
        // პროდუქტის სურათი
        ->addColumn('show_photo', function ($product) {
            if (!$product->image) {
                return '<span class="label label-default">No Image</span>';
            }
            return'<img src="'.url($product->image).'" class="img-thumbnail img-zoom-trigger" 
                     style="width:50px; height:50px; object-fit:cover; cursor:pointer;">';
        })
        // სტატუსის Badge (Active/Inactive)
        ->addColumn('status_stock', function ($product) {
    $status = $product->product_status == 1
        ? '<span class="label label-success">Active</span>'
        : '<span class="label label-danger">Inactive</span>';

    $stock = $product->in_warehouse == 1
        ? '<span class="label label-primary">In Stock</span>'
        : '<span class="label label-warning">Out of Stock</span>';

    return $status . ' ' . $stock;
})
        // ზომების ფორმატირება (თითოეული ზომა ცალკე Badge-ად)
        ->addColumn('format_sizes', function ($product) {
            if (!$product->sizes) {
                return '<span class="text-muted">-</span>';
            }
            $sizesArray = explode(',', $product->sizes);
            $html = '';
            foreach ($sizesArray as $size) {
                $html .= '<span class="label label-info" style="margin-right:2px; display:inline-block; margin-bottom:2px;">' . e(trim($size)) . '</span>';
            }
            return $html;
        })
        // მოქმედების ღილაკები
        ->addColumn('action', function ($product) {
    if (auth()->user()->role !== 'admin') return '';
    return '<center>'.
           '<a onclick="editForm(' . $product->id . ')" class="btn btn-primary btn-xs"><i class="glyphicon glyphicon-edit"></i> Edit</a> ' .
           '<a onclick="deleteData(' . $product->id . ')" class="btn btn-danger btn-xs"><i class="glyphicon glyphicon-trash"></i> Delete</a>'.
           '</center>';
})
        // მივუთითებთ რომელ სვეტებშია HTML კოდი
        ->rawColumns(['category_name', 'show_photo', 'status_stock', 'format_sizes', 'action'])
        ->make(true);
}

// ახალი — categories.sizes სტრიქონს ანაწევრებს
public function getSizes($category_id)
{
    $category = \App\Models\Category::findOrFail($category_id);

    if (!$category->sizes) {
        return response()->json([]);
    }

    // "S,M,L" -> [["name":"S"], ["name":"M"], ["name":"L"]]
    // ვინარჩუნებთ იგივე სტრუქტურას რომ JS არ შეიცვალოს
    $sizes = collect(explode(',', $category->sizes))
        ->map(fn($s) => ['name' => trim($s)])
        ->filter(fn($s) => $s['name'] !== '')
        ->values();

    return response()->json($sizes);
}
}