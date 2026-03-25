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
        // 'status_id'    => 'required',
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

    public function apiProductsOut(Request $request)
{
   $isAdmin = auth()->user()->role === 'admin';

    $query = Product_Order::with(['product', 'customer.city', 'orderStatus']);

    // დავალიანების ფილტრი
    if ($request->debt_only == 1) {
        $query->whereRaw('(price_georgia - IFNULL(discount,0)) > (IFNULL(paid_tbc,0) + IFNULL(paid_bog,0) + IFNULL(paid_lib,0) + IFNULL(paid_cash,0))');
    }

    $productOrder = $query->get();

    return Datatables::of($productOrder)
        ->addColumn('order_id', function ($item) {
            return '#' . $item->id;
        })
        ->addColumn('show_photo', function ($item) {
            if (!$item->product || !$item->product->image) {
                return '<span class="label label-default">No Image</span>';
            }
            return '<img src="' . url($item->product->image) . '" 
                        class="img-thumbnail img-zoom-trigger"
                        style="width:50px; height:50px; object-fit:cover; cursor:pointer;">';
        })
        ->addColumn('products_name', function ($item) {
            return $item->product->name ?? 'N/A';
        })
        ->addColumn('product_code', function ($item) {
            return $item->product->product_code ?? '-';
        })
        ->editColumn('created_at', function($item){
            return $item->created_at->format('Y-m-d H:i:s');
        })
        ->addColumn('product_size', function ($item) {
            return $item->product_size
                ? '<span class="label label-info">' . e($item->product_size) . '</span>'
                : '<span class="text-muted">-</span>';
        })
        ->addColumn('customer_name', function ($item) {
            return $item->customer->name ?? 'N/A';
        })
        ->addColumn('prices', function ($item) use ($isAdmin) {
            $geo = '<b>GE:</b> ' . $item->price_georgia . ' ₾';
            $usa = $isAdmin ? '<br><b>US:</b> ' . $item->price_usa . ' $' : '';
            return $geo . $usa;
        })
        ->addColumn('payment', function ($item) {
    $geo  = $item->price_georgia - ($item->discount ?? 0);
    $paid = ($item->paid_tbc ?? 0) + ($item->paid_bog ?? 0) + 
            ($item->paid_lib ?? 0) + ($item->paid_cash ?? 0);
    $diff = $geo - $paid;

    if ($diff > 0.01) {
        return '<span style="color:red; font-weight:bold;">
                    <i class="fa fa-exclamation-circle"></i> დავალიანება: ' . number_format($diff, 2) . ' ₾
                </span>';
    } elseif ($diff < -0.01) {
        return '<span style="color:green; font-weight:bold;">
                    <i class="fa fa-plus-circle"></i> ზედმეტი: ' . number_format(abs($diff), 2) . ' ₾
                </span>';
    } else {
        return '<span style="color:green;">
                    <i class="fa fa-check-circle"></i> გადახდილია
                </span>';
    }
})
        ->addColumn('customer_contact', function ($item) {
            $customer = $item->customer;
            if (!$customer) return '<span class="text-muted">-</span>';

            $city    = $customer->city->name ?? '-';
            $address = $customer->address ?? '-';
            $tel     = $customer->tel ?? '-';
            $alt     = $customer->alternative_tel ?? '';

            $html  = '<small>';
            $html .= '<i class="fa fa-map-marker"></i> ' . e($city) . ', ' . e($address) . '<br>';
            $html .= '<i class="fa fa-phone"></i> ' . e($tel);
            if ($alt) $html .= ' / ' . e($alt);
            $html .= '</small>';
            return $html;
        })
        ->addColumn('status_label', function ($item) use ($isAdmin) {
            $color = $item->orderStatus->color ?? 'default';
            $name  = $item->orderStatus->name  ?? 'Pending';

            if ($isAdmin) {
                return '<span class="label label-' . $color . '" 
                            style="cursor:pointer; font-size:12px; padding:4px 8px;" 
                            onclick="openStatusModal(' . $item->id . ', ' . $item->status_id . ')" 
                            title="შეცვალე სტატუსი">
                            ' . $name . ' <i class="fa fa-pencil" style="margin-left:4px;font-size:10px;opacity:0.7;"></i>
                        </span>';
            }

            return '<span class="label label-' . $color . '" 
                        style="font-size:12px; padding:4px 8px;">
                        ' . $name . '
                    </span>';
        })
        ->addColumn('action', function ($item) use ($isAdmin) {
            if (!$isAdmin) return '';
            $exportPdfUrl = route('exportPDF.productOrder', ['id' => $item->id]);
            return '<center>' .
                '<a onclick="editForm(' . $item->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a> ' .
                '<a onclick="deleteData(' . $item->id . ')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a> ' .
                '<a href="' . $exportPdfUrl . '" target="_blank" class="btn btn-info btn-xs" title="PDF"><i class="fa fa-file-pdf-o"></i></a>' .
                '</center>';
        })
        ->rawColumns(['show_photo', 'product_size', 'prices', 'payment', 'customer_contact', 'status_label', 'action'])
        ->make(true);
}
    public function exportProductOrderAll()
    {
        $product_Order = Product_Order::with(['product', 'customer', 'orderStatus'])->get();
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
    $product_order = Product_Order::with([
    'product' => fn($q) => $q->withoutGlobalScope('active'),
    'customer.city',
    'orderStatus'
])->findOrFail($id);
    
    // პროდუქტის სურათის base64-ად გადაყვანა
    $imageBase64 = null;
    if ($product_order->product->image) {
       $imagePath = public_path(ltrim($product_order->product->image, '/'));
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
    if (empty($ids)) { abort(400, 'No orders selected'); }

    // ვიღებთ ორდერებს და პროდუქტებს ყველანაირი სკოუპის გარეშე
    $product_Order = Product_Order::withoutGlobalScope('active')
        ->with([
            'product' => fn($q) => $q->withoutGlobalScope('active'),
            'customer.city',
            'orderStatus'
        ])
        ->whereIn('id', $ids)
        ->get();

    foreach ($product_Order as $order) {
        $order->imageBase64 = null;

        // თუ პროდუქტი არსებობს (თუნდაც წაშლილი)
        if ($order->product && $order->product->image) {
            
            // 1. ვასუფთავებთ გზას: ვაშორებთ ზედმეტ სლეშებს და 'public'-ს თუ წერია
            $imageField = ltrim($order->product->image, '/');
            
            // 2. ვცდით სხვადასხვა გზას ფაილის მოსაძებნად
            $pathsToTry = [
                public_path($imageField),
                base_path('public/' . $imageField),
                // ზოგჯერ ბაზაში წერია 'upload/products/...' და public_path ამატებს კიდევ ერთ public-ს
            ];

            $debugLog = [];
foreach ($pathsToTry as $path) {
    $debugLog[] = $path . ' → ' . (file_exists($path) ? 'EXISTS' : 'NOT FOUND');
    if (file_exists($path) && !is_dir($path)) {
        $imageData = file_get_contents($path);
        $mimeType = mime_content_type($path);
        $order->imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        break;
    }
}

        }
    }

    $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order'));
    return $pdf->download('filtered_orders.pdf');
}
}