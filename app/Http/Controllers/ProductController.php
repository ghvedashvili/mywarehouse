<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,staff,sale_operator');
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
        'price_geo'    => 'required', // შეიცვალა პატარა ასოზე
        'category_id'  => 'required|exists:categories,id',
        'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'product_sizes'=> 'nullable|array', // Blade-ში სახელია product_sizes[]
    ]);

    $input = $request->all();

    // ველების სინქრონიზაცია ბაზის სვეტებთან
    $input['price_geo'] = $request->price_geo; 
    $input['product_status'] = $request->has('product_status') ? 1 : 0;
    $input['in_warehouse']   = $request->has('in_warehouse') ? 1 : 0;

    // ზომების დამუშავება (Blade-დან მოდის product_sizes სახელით)
    $input['sizes'] = $request->has('product_sizes') ? implode(',', $request->product_sizes) : null;

    if ($request->hasFile('image')) {
        $filename = Str::slug($request->product_code, '-') . '-' . time() . '.' . $request->image->getClientOriginalExtension();
        if (config('filesystems.default') === 's3') {
            Storage::disk('s3')->putFileAs('products', $request->file('image'), $filename, 'public');
            $input['image'] = 'products/' . $filename;
        } else {
            $request->image->move(public_path('/upload/products/'), $filename);
            $input['image'] = '/upload/products/' . $filename;
        }
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
    // ვიყენებთ with('category')-ს, თუ მომავალში დაგჭირდებათ კატეგორიის მონაცემებიც
    $product = Product::findOrFail($id);

    // image_url accessor-ი ავტომატურად გამოითვლება

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
        'price_geo'    => 'required', // შეიცვალა პატარა ასოზე
        'category_id'  => 'required|exists:categories,id',
        'image'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'product_sizes'=> 'nullable|array',
    ]);

    $input = $request->all();
    $input['price_geo'] = $request->price_geo;
    $input['product_status'] = $request->has('product_status') ? 1 : 0;
    $input['in_warehouse']   = $request->has('in_warehouse') ? 1 : 0;
    $input['sizes'] = $request->has('product_sizes') ? implode(',', $request->product_sizes) : null;

    if ($request->hasFile('image')) {
        $filename = Str::slug($request->product_code, '-') . '-' . time() . '.' . $request->image->getClientOriginalExtension();
        if (config('filesystems.default') === 's3') {
            if ($product->image && !str_starts_with($product->image, '/')) {
                Storage::disk('s3')->delete($product->image);
            }
            Storage::disk('s3')->putFileAs('products', $request->file('image'), $filename, 'public');
            $input['image'] = 'products/' . $filename;
        } else {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            }
            $request->image->move(public_path('/upload/products/'), $filename);
            $input['image'] = '/upload/products/' . $filename;
        }
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
            'success' => true,
            'cant_delete' => true,
            'message' => 'პროდუქტი გამოიყენება ორდერ(ებ)ში და ვერ წაიშლება. პროდუქტი Inactive-ად დაინიშნა.'
        ]);
    }

    $product->delete(); // იძახებს Model-ის override delete()-ს → status='deleted'

    return response()->json([
        'success' => true,
        'message' => 'Product Deleted Successfully'
    ]);
}

public function updateStatus(Request $request, $id)
{
    $product = Product::findOrFail($id);
    $product->update(['product_status' => $request->product_status ? 1 : 0]);
    return response()->json(['success' => true]);
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
        if (!$product->image_url) {
            return '<span class="label label-default">No Image</span>';
        }
        return '<img src="'.$product->image_url.'" class="img-thumbnail img-thumb"
                 style="width:50px; height:50px; object-fit:cover; cursor:pointer;"
                 data-src="'.$product->image_url.'">';
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
   $products = \App\Models\Product::with('category')->select('products.*')->orderBy('updated_at', 'DESC');

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
            return '<img src="'.$product->image_url.'" class="img-thumbnail img-thumb" style="width:50px; height:50px; object-fit:cover;">';
        })
        ->addColumn('status_stock', function ($product) {
    $label = $product->product_status == 1 ? 'Active' : 'Inactive';
    $color = $product->product_status == 1 ? 'bg-success' : 'bg-danger';
    return '<span class="badge ' . $color . '" style="cursor:pointer;" onclick="openStatusModal(' . $product->id . ',' . $product->product_status . ')">' . $label . '</span>';
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
    $role = auth()->user()->role;
    if ($role === 'admin') {
        return '<div class="d-flex gap-1 justify-content-center">' .
               '<a onclick="editForm(' . $product->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a>' .
               '<a onclick="deleteData(' . $product->id . ')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a>' .
               '</div>';
    }
    if ($role === 'sale_operator') {
        return '<a onclick="editForm(' . $product->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a>';
    }
    return '';
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