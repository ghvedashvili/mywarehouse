<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\Product_Order;
use App\Exports\ExportProdukOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class ProductOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $products = Product::orderBy('name','ASC')->pluck('name','id');
    
    // ეს გვჭირდება JavaScript-ისთვის, რომ ფასები ამოიღოს
    $all_products = Product::where('product_status', 1)->get();
    
    $customers = Customer::orderBy('name','ASC')->pluck('name','id');
    $statuses = OrderStatus::all(); 

    return view('product_Order.index', compact('products', 'customers', 'statuses', 'all_products'));
    }

    public function store(Request $request)
{
    $this->validate($request, [
        'product_id'   => 'required',
        'customer_id'  => 'required',
        'status_id'    => 'required',
        // 'product_size' => 'required', // სურვილისამებრ, თუ ზომა სავალდებულოა
    ]);
$product = Product::findOrFail($request->product_id);

    // 2. შევამოწმოთ სტატუსი
    if ($product->product_status != 1) {
        // ვაბრუნებთ შეცდომას JSON ფორმატში, რომელსაც Ajax დაიჭერს
        return response()->json([
            'success' => false,
            'message' => 'პროდუქტი არაა აქტიური, განაახლეთ გვერდი!'
        ], 422); // 422 არის Unprocessable Entity
    }

    // თუ სტატუსი 1-ია, ვაგრძელებთ ჩვეულებრივად...
    $data = $request->all();
 

    // 1. ვამატებთ სისტემაში შესული მომხმარებლის ID-ს
    $data['user_id'] = auth()->id();

    // 2. კურიერის ლოგიკა: ვიღებთ რიცხვებს (decimal), თუ არ არის - ვწერთ 0-ს
    $data['courier_price_international'] = $request->courier_price_international ?: 0;
    $table_courier_tbilisi = $request->courier_price_tbilisi ?: 0;
    $data['courier_price_tbilisi'] = $table_courier_tbilisi;
    $data['courier_price_region'] = $request->courier_price_region ?: 0;

    // 3. ფასდაკლება და სხვა ველები
    $data['discount'] = $request->discount ?: 0;
    $data['product_size'] = $request->product_size; // Blade-დან წამოსული ზომა

    // 4. გადახდები (უცვლელია, უბრალოდ ვამოწმებთ რომ null არ იყოს)
    $data['paid_tbc']  = $request->paid_tbc ?: 0;
    $data['paid_bog']  = $request->paid_bog ?: 0;
    $data['paid_lib']  = $request->paid_lib ?: 0;
    $data['paid_cash'] = $request->paid_cash ?: 0;

    // 5. შენახვა (დარწმუნდი, რომ მოდელის სახელი Product_Order სწორია)
    Product_Order::create($data);

    return response()->json([
        'success' => true, 
        'message' => 'Order Created Successfully'
    ]);
}

    public function edit($id)
    {
        $product_Order = Product_Order::findOrFail($id);
        return response()->json($product_Order);
    }

    public function update(Request $request, $id)
    {
        $order = Product_Order::findOrFail($id);
        $data = $request->all();
        
        $data['courier_servise_international'] = $request->has('courier_servise_international') ? 1 : 0;
        $data['courier_servise_local'] = $request->has('courier_servise_local') ? 1 : 0;

        $order->update($data);

        return response()->json(['success' => true, 'message' => 'Order Updated Successfully']);
    }

    public function destroy($id)
    {
        Product_Order::destroy($id);
        return response()->json(['success' => true, 'message' => 'Order Deleted Successfully']);
    }

    public function apiProductsOut()
{
    $productOrder = Product_Order::with(['product', 'customer', 'status'])->get();

    return Datatables::of($productOrder)
        ->addColumn('products_name', function ($item) {
            return $item->product->name ?? 'N/A';
        })
        ->addColumn('customer_name', function ($item) {
            return $item->customer->name ?? 'N/A';
        })
        ->addColumn('prices', function ($item) {
            return "<b>GE:</b> {$item->price_georgia} ₾<br> <b>US:</b> {$item->price_usa} $";
        })
        ->addColumn('status_label', function ($item) {
            $color = $item->status->color ?? 'default';
            $name = $item->status->name ?? 'Pending';
            return '<span class="label label-'.$color.'">'.$name.'</span>';
        })
        ->addColumn('action', function ($item) {
            // ლინკი უნდა იყოს აქ, რომ თითოეული პროდუქტის ID სწორად ჩაჯდეს
            $exportPdfUrl = route('exportPDF.productOrder', ['id' => $item->id]);
            
            return '<center>'.
                '<a onclick="editForm('. $item->id .')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a> ' .
                '<a onclick="deleteData('. $item->id .')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a> ' .
                '<a href="'. $exportPdfUrl .'" target="_blank" class="btn btn-info btn-xs" title="PDF Invoice"><i class="fa fa-file-pdf-o"></i></a>'.
                '</center>';
        })
        ->rawColumns(['prices', 'status_label', 'action'])
        ->make(true);
}
    public function exportProductOrderAll()
    {
        $product_Order = Product_Order::with(['product', 'customer', 'status'])->get();
        $pdf = Pdf::loadView('product_Order.productOrderAllPDF', compact('product_Order'));
        return $pdf->download('all_orders.pdf');
    }

    public function exportProductOrder($id)
    {
        $product_order = Product_Order::with(['product', 'customer', 'status'])->findOrFail($id);
        $pdf = Pdf::loadView('product_Order.productOrderPDF', compact('product_order'));
        return $pdf->download($id.'_invoice.pdf');
    }

    public function exportExcel()
    {
        return (new ExportProdukOrder)->download('orders.xlsx');
    }
}