<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\City;
use App\Models\Courier;
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
      $cities = City::all(); // ეს დაამატე
    $customers = Customer::with('city')->get();
    $statuses = OrderStatus::all(); 
$courier = Courier::first(); // ეს დაამატე

    return view('product_Order.index', compact('products', 'customers', 'statuses', 'all_products', 'cities', 'courier'));
    }

    public function store(Request $request)
{
    $this->validate($request, [
        'product_id'   => 'required',
        'customer_id'  => 'required',
        'status_id'    => 'required',
    ]);

    $product = Product::findOrFail($request->product_id);

    if ($product->product_status != 1) {
        return response()->json([
            'success' => false,
            'message' => 'პროდუქტი არაა აქტიური!'
        ], 422);
    }

    $data = $request->all();
    $user = auth()->user();

    $data['user_id'] = $user->id;

    // ❗ ყოველთვის სერვერიდან ავიღოთ ფასები
    $data['price_georgia'] = $product->price_geo;
    $data['price_usa'] = $product->price_usa;

    // ❗ STAFF შეზღუდვა
    if ($user->role === 'staff') {
        $data['discount'] = 0;
        $data['paid_tbc'] = 0;
        $data['paid_bog'] = 0;
        $data['paid_lib'] = 0;
    }

    // default values
    $data['discount'] = $data['discount'] ?? 0;
    $data['paid_tbc'] = $data['paid_tbc'] ?? 0;
    $data['paid_bog'] = $data['paid_bog'] ?? 0;
    $data['paid_lib'] = $data['paid_lib'] ?? 0;
    $data['paid_cash'] = $data['paid_cash'] ?? 0;
$courier = Courier::first();
$data['courier_price_international'] = $courier->international_price ?? 30;
$data['courier_servise_local'] = $request->has('courier_servise_local') ? 1 : 0;
$data['courier_price_tbilisi'] = $request->has('courier_servise_local') ? ($courier->tbilisi_price ?? 6) : 0;
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
        if (auth()->user()->role !== 'admin') {
        unset($data['status_id']);
    }
        $courier = Courier::first();
        $data['courier_price_international'] = $courier->international_price ?? 30;
$data['courier_servise_local'] = $request->has('courier_servise_local') ? 1 : 0;
$data['courier_price_tbilisi'] = $data['courier_servise_local'] ? ($courier->tbilisi_price ?? 6) : 0;


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
    return '<span class="label label-'.$color.'" 
                style="cursor:pointer; font-size:12px; padding:4px 8px;" 
                onclick="openStatusModal('.$item->id.', '.$item->status_id.')" 
                title="შეცვალე სტატუსი">
                '.$name.' <i class="fa fa-pencil" style="margin-left:4px;font-size:10px;opacity:0.7;"></i>
            </span>';
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
public function updateStatus(Request $request, $id)
{
    $order = Product_Order::findOrFail($id);
    $order->update(['status_id' => $request->status_id]);
    
    return response()->json(['success' => true, 'message' => 'სტატუსი განახლდა']);
}
    public function exportProductOrder($id)
{
    $product_order = Product_Order::with(['product', 'customer', 'status'])->findOrFail($id);
    
    // პროდუქტის სურათის base64-ად გადაყვანა
    $imageBase64 = null;
    if ($product_order->product->image) {
        $imagePath = public_path($product_order->product->image);
        if (file_exists($imagePath)) {
            $imageData = file_get_contents($imagePath);
            $mimeType = mime_content_type($imagePath);
            $imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        }
    }
    
    $pdf = Pdf::loadView('product_Order.productOrderPDF', compact('product_order', 'imageBase64'));
    return $pdf->download($id.'_invoice.pdf');
}

    public function exportExcel()
    {
        return (new ExportProdukOrder)->download('orders.xlsx');
    }
    public function exportFilteredOrders(Request $request)
{
    $ids = $request->input('ids', []);
    
    if (empty($ids)) {
        abort(400, 'No orders selected');
    }

    $product_Order = Product_Order::with(['product', 'customer.city', 'status'])
    ->whereIn('id', $ids)
    ->get();
// dd($product_Order->first()->customer->city);
    // თითოეული პროდუქტის სურათი base64-ად
    $product_Order->transform(function ($order) {
        $order->imageBase64 = null;
        if ($order->product && $order->product->image) {
            $imagePath = public_path($order->product->image);
            if (file_exists($imagePath)) {
                $imageData = file_get_contents($imagePath);
                $mimeType = mime_content_type($imagePath);
                $order->imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
            }
        }
        return $order;
    });

    $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order'));
    return $pdf->download('filtered_orders.pdf');
}
}