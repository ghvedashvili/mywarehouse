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
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class ProductOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
{
    $products = Product::orderBy('name','ASC')->pluck('name','id');

    // მხოლოდ active პროდუქტები — Global Scope ისედაც ფილტრავს
    $all_products = Product::where('product_status', 1)->get();

    $cities = City::all();
    $customers = Customer::with('city')->get();
    $statuses = OrderStatus::all(); 
    $courier = Courier::first();

    return view('product_Order.index', compact('products', 'customers', 'statuses', 'all_products', 'cities', 'courier'));
}

    public function store(Request $request)
{
    $this->validate($request, [
        'product_id'   => 'required',
        'customer_id'  => 'required',
        // 'status_id'    => 'required',
    ]);

    $product = Product::with('category')->findOrFail($request->product_id);



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
// ახალი კოდი (store-ში):
$courier = Courier::first();

// international ყოველთვის
$categoryPrice = $product->category->international_courier_price ?? null;
$data['courier_price_international'] = $categoryPrice ?? ($courier->international_price ?? 30);

// სამივე 0-ზე დააყენე, შემდეგ მონიშნული შეავსე
$data['courier_price_tbilisi'] = 0;
$data['courier_price_region']  = 0;
$data['courier_price_village'] = 0;

$courierType = $request->courier_type ?? 'none';
$data['courier_servise_local'] = $courierType;

if ($courierType === 'tbilisi') {
    $data['courier_price_tbilisi'] = $courier->tbilisi_price ?? 6;
} elseif ($courierType === 'region') {
    $data['courier_price_region'] = $courier->region_price ?? 9;
} elseif ($courierType === 'village') {
    $data['courier_price_village'] = $courier->village_price ?? 13;
}
    Product_Order::create($data);

    return response()->json([
        'success' => true,
        'message' => 'Order Created Successfully'
    ]);
}

    public function edit($id)
{
    $product_Order = Product_Order::findOrFail($id);

    // inactive პროდუქტი withoutGlobalScope-ით
    $product = Product::withoutGlobalScope('active')
        ->find($product_Order->product_id);

    // JS-ს გადავცეთ პროდუქტის მონაცემები (inactive-ც)
    $product_Order->current_product = $product ? [
        'id'            => $product->id,
        'name'          => $product->name,
        'price_geo'     => $product->price_geo,
        'price_usa'     => $product->price_usa,
        'sizes'         => $product->sizes,
        'image'         => $product->image ? url($product->image) : null,
        'product_status'=> $product->product_status,
    ] : null;

    return response()->json($product_Order);
}

    public function update(Request $request, $id)
{
    $order = Product_Order::findOrFail($id);
    $data = $request->all();

    if (auth()->user()->role !== 'admin') {
        unset($data['status_id']);
    }

    // ❗ ბანკების დაზღვევა Null-ისგან (რომ SQL Error არ ამოაგდოს)
    $data['paid_tbc']  = $request->paid_tbc ?? 0;
    $data['paid_bog']  = $request->paid_bog ?? 0;
    $data['paid_lib']  = $request->paid_lib ?? 0;
    $data['paid_cash'] = $request->paid_cash ?? 0;
    $data['discount']  = $request->discount ?? 0;

    $courier = Courier::first();
    $productId = $request->product_id ?? $order->product_id;
    $product = Product::with('category')->findOrFail($productId);

    // international ფასი
    $categoryPrice = $product->category->international_courier_price ?? null;
    $data['courier_price_international'] = $categoryPrice ?? ($courier->international_price ?? 30);

    // კურიერის ფასების განულება და ხელახლა მინიჭება
    $data['courier_price_tbilisi'] = 0;
    $data['courier_price_region']  = 0;
    $data['courier_price_village'] = 0;

    $courierType = $request->courier_type ?? 'none';
    $data['courier_servise_local'] = $courierType; // ეს ველი ინახავს "tbilisi", "region" და ა.შ.

    if ($courierType === 'tbilisi') {
        $data['courier_price_tbilisi'] = $courier->tbilisi_price ?? 6;
    } elseif ($courierType === 'region') {
        $data['courier_price_region'] = $courier->region_price ?? 9;
    } elseif ($courierType === 'village') {
        $data['courier_price_village'] = $courier->village_price ?? 13;
    }

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

   $query = Product_Order::withoutGlobalScope('active')
        ->with(['product', 'customer.city', 'orderStatus'])
        ->latest();

    // დავალიანების ფილტრი
    if ($request->debt_only == 1) {
        $query->whereRaw('(price_georgia - IFNULL(discount,0)) > (IFNULL(paid_tbc,0) + IFNULL(paid_bog,0) + IFNULL(paid_lib,0) + IFNULL(paid_cash,0))');
    }
if ($request->has('statuses')) {
    $statuses = $request->input('statuses');
    $query->whereIn('status_id', $statuses);
}
if ($request->get('show_deleted') == 1) {
        $query->where('status', 'deleted');
    } else {
        // თუ სვიჩერი გამორთულია, აჩვენოს მხოლოდ აქტიურები
        $query->where('status', 'active');
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
                        style="width:60px; height:60px; object-fit:cover; cursor:pointer;">';
        })
        ->addColumn('product_info', function ($item) {
    $name = $item->product->name ?? 'N/A';
    $code = $item->product->product_code ?? '-';
    $size = $item->product_size
        ? '<span class="label label-info">' . e($item->product_size) . '</span>'
        : '<span class="text-muted">-</span>';

    return '<div>' . e($name) . '</div>
            <div><small class="text-muted">' . e($code) . '</small></div>
            <div>' . $size . '</div>';
})
        ->editColumn('created_at', function($item){
            return $item->created_at->format('Y-m-d H:i:s');
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
                    <i class="fa fa-exclamation-circle"></i> -' . number_format($diff, 2) . ' ₾
                </span>';
    } elseif ($diff < -0.01) {
        return '<span style="color:green; font-weight:bold;">
                    <i class="fa fa-plus-circle"></i> + ' . number_format(abs($diff), 2) . ' ₾
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
        }) ->addColumn('status_label', function ($item) use ($isAdmin) {
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
    
    if ($item->status === 'deleted') {
        return '<center>' .
            '<a onclick="restoreData(' . $item->id . ')" class="btn btn-success btn-xs" title="Restore"><i class="fa fa-refresh"></i> აღდგენა</a>' .
            '</center>';
    }

    $exportPdfUrl = route('exportPDF.productOrder', ['id' => $item->id]);
    $email = e($item->customer->email ?? '');
    $customerId = $item->customer_id;
    
    return '<center>' .
        '<a onclick="editForm(' . $item->id . ')" class="btn btn-primary btn-xs" title="Edit"><i class="fa fa-edit"></i></a> ' .
        '<a onclick="deleteData(' . $item->id . ')" class="btn btn-danger btn-xs" title="Delete"><i class="fa fa-trash"></i></a> ' .
        '<a href="' . $exportPdfUrl . '" target="_blank" class="btn btn-info btn-xs" title="PDF"><i class="fa fa-file-pdf-o"></i></a> ' .
        '<a onclick="openMailModal(' . $item->id . ', ' . $customerId . ', \'' . $email . '\')" class="btn btn-default btn-xs" title="Mail"><i class="fa fa-envelope"></i></a>' .
        '</center>';
})
        ->rawColumns(['show_photo', 'product_info', 'prices', 'payment', 'customer_contact', 'status_label', 'action'])
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
// ProductOrderController.php

public function restore($id)
{
    $order = Product_Order::withoutGlobalScope('active')->findOrFail($id);
    $order->update(['status' => 'active']);

    return response()->json([
        'success' => true, 
        'message' => 'ორდერი წარმატებით აღდგა'
    ]);
}
    public function exportProductOrder($id)
{
    // 1. მოგვაქვს კონკრეტული ორდერი საჭირო კავშირებით
    $order = Product_Order::withoutGlobalScope('active')
        ->with([
            'product' => fn($q) => $q->withoutGlobalScope('active'),
            'customer.city',
            'orderStatus'
        ])
        ->findOrFail($id);

    // 2. ლოგოს მომზადება (Base64)
    $logoBase64 = null;
    $logoPath = public_path('assets/img/logo.png');
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $mimeType = mime_content_type($logoPath);
        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
    }

    // 3. პროდუქტის სურათის მომზადება (Base64)
    $order->imageBase64 = null;
    if ($order->product && $order->product->image) {
        $imageField = ltrim($order->product->image, '/');
        $fullPath = public_path($imageField);
        if (file_exists($fullPath) && !is_dir($fullPath)) {
            $order->imageBase64 = 'data:' . mime_content_type($fullPath) . ';base64,' . base64_encode(file_get_contents($fullPath));
        }
    }

    // 4. კრიტიკული მომენტი: ერთ ორდერს ვაქცევთ კოლექციად (მასივად)
    // რადგან productOrderFilteredPDF ფაილში გიწერიათ @foreach($product_Order as $product_order)
    $product_Order = collect([$order]);

    // 5. PDF-ის გენერაცია იგივე ბლეიდით
    $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
        ->setPaper('a4')
        ->setOptions([
            'defaultFont' => 'dejavu sans', // ქართული შრიფტისთვის
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

    return $pdf->download('Invoice_#' . $id . '.pdf');
}

    public function exportExcel()
    {
        return (new ExportProdukOrder)->download('orders.xlsx');
    }
public function exportFilteredOrders(Request $request)
{

$path = public_path('assets/img/logo.png');
    
    // ეს კოდი გააჩერებს ყველაფერს და ეკრანზე დაგიწერთ სიმართლეს:
    
    $ids = $request->input('ids', []);
    if (empty($ids)) {
        abort(400, 'No orders selected');
    }

    // ვიღებთ ორდერებს
    $product_Order = Product_Order::withoutGlobalScope('active')
        ->with([
            'product' => fn($q) => $q->withoutGlobalScope('active'),
            'customer.city',
            'orderStatus'
        ])
        ->whereIn('id', $ids)
        ->get();

    // ლოგოს Base64-ად გადაყვანა (მთავარი ლოგო)
    $logoPath = public_path('assets/img/logo.png');
    $logoBase64 = null;
    
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $mimeType = mime_content_type($logoPath);
        $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($logoData);
    } else {
        // თუ ფაილი არ არსებობს, ჩავწეროთ ლოგში, რომ ვიცოდეთ
        \Log::error('Logo not found at: ' . $logoPath);
    }

    // პროდუქტების სურათების Base64-ად გადაყვანა
    foreach ($product_Order as $order) {
        $order->imageBase64 = null;
        if ($order->product && $order->product->image) {
            $imageField = ltrim($order->product->image, '/');
            $pathsToTry = [
                public_path($imageField),
                base_path('public/' . $imageField),
            ];

            foreach ($pathsToTry as $path) {
                if (file_exists($path) && !is_dir($path)) {
                    $imageData = file_get_contents($path);
                    $mimeType = mime_content_type($path);
                    $order->imageBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    break;
                }
            }
        }
    }

    // აუცილებელია გადავცეთ logoBase64 compact-ში
    $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
        ->setPaper('a4')
        ->setOptions([
            'defaultFont' => 'dejavu sans', // ქართული შრიფტისთვის
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true
        ]);

    return $pdf->download('filtered_orders.pdf');
}
public function sendMail(Request $request, $id)
{
    $request->validate([
        'email'   => 'required|email',
        'subject' => 'required|string|max:255',
        'body'    => 'nullable|string',
    ]);

    // ორდერის ჩატვირთვა
    $order = Product_Order::withoutGlobalScope('active')
        ->with([
            'product'      => fn($q) => $q->withoutGlobalScope('active'),
            'customer.city',
            'orderStatus'
        ])
        ->findOrFail($id);

    // ლოგო Base64
    $logoBase64 = null;
    $logoPath   = public_path('assets/img/logo.png');
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:' . mime_content_type($logoPath) . ';base64,' . base64_encode(file_get_contents($logoPath));
    }

    // პროდუქტის სურათი Base64
    $order->imageBase64 = null;
    if ($order->product && $order->product->image) {
        $imgPath = public_path(ltrim($order->product->image, '/'));
        if (file_exists($imgPath) && !is_dir($imgPath)) {
            $order->imageBase64 = 'data:' . mime_content_type($imgPath) . ';base64,' . base64_encode(file_get_contents($imgPath));
        }
    }

    // იგივე blade რომელიც filtered PDF-ისთვის გამოიყენება
    $product_Order = collect([$order]);

    $pdf = Pdf::loadView('product_Order.productOrderFilteredPDF', compact('product_Order', 'logoBase64'))
        ->setPaper('a4')
        ->setOptions([
            'defaultFont'        => 'dejavu sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled'    => true,
        ]);

    $pdfContent = $pdf->output();

    // მეილის გაგზავნა PDF attachment-ით
    $subject = $request->subject;
    $body    = $request->body ?? '';

    Mail::send([], [], function (Message $msg) use ($request, $pdfContent, $subject, $body, $id) {
    $msg->to($request->email)
        ->subject($subject)
        ->text($body ?: 'გთხოვთ იხილოთ თანდართული invoice.')
        ->attachData($pdfContent, 'Invoice_#' . $id . '.pdf', [
            'mime' => 'application/pdf',
        ]);
});

    // email-ის შენახვა კლიენტზე
    if ($request->save_email == 1 && $request->customer_id) {
        $customer = Customer::find($request->customer_id);
        if ($customer) {
            $customer->update(['email' => $request->email]);
        }
    }

    return response()->json(['success' => true, 'message' => 'მეილი გაიგზავნა']);
}
}