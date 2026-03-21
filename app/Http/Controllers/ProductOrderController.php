<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Exports\ExportProdukOrder;
use App\Models\Product;
use App\Models\Product_Order;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;


class ProductOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin,staff');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $products = Product::orderBy('name','ASC')
            ->get()
            ->pluck('name','id');

        $customers = Customer::orderBy('name','ASC')
            ->get()
            ->pluck('name','id');

        $invoice_data = Product_Order::all();
        return view('product_Order.index', compact('products','customers', 'invoice_data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
{
    $this->validate($request, [
        'product_id'  => 'required',
        'customer_id' => 'required',
        'qty'         => 'required|numeric',
        // 'date'     => 'required', // თუ თარიღი არ გჭირდება ბაზაში, ესეც არ გინდა
    ]);

    // 1. მხოლოდ მონაცემების შენახვა Product_Order ცხრილში
    $input = $request->all();
    
    // თუ ბაზაში მაინც გაქვს 'tanggal' სვეტი, დატოვე ეს ხაზი:
    // $input['tanggal'] = $request->date; 

    \App\Models\Product_Order::create($input);

    /* წაშალე ან დააკომენტარე ქვემოთა ნაწილი, 
       რადგან ის იწვევს შეცდომას:
    
    $product = \App\Models\Product::findOrFail($request->product_id);
    $product->qty -= $request->qty; 
    $product->update(); 
    */

    return response()->json([
        'success' => true,
        'message' => 'Order Created Successfully'
    ]);
}

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $product_Order = Product_Order::find($id);
        return $product_Order;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
{
    $this->validate($request, [
        'product_id'  => 'required',
        'customer_id' => 'required',
        'qty'         => 'required'
        // 'tanggal'  => 'required' // თუ ბაზაში არ გაქვს, დატოვე დაკომენტარებული
    ]);

    // 1. ვპოულობთ ჩანაწერს product_Order ცხრილში
    $product_Order = Product_Order::findOrFail($id);
    
    // 2. ვანახლებთ მხოლოდ ამ ჩანაწერს
    $product_Order->update($request->all());

    /* წაშალე ეს ბლოკი სრულად, რადგან სწორედ ეს იწვევს შეცდომას:
       
       $product = Product::findOrFail($request->product_id);
       $product->qty -= $request->qty;
       $product->update(); 
    */

    return response()->json([
        'success' => true,
        'message' => 'Product Out Updated Successfully'
    ]);
}

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Product_Order::destroy($id);

        return response()->json([
            'success'    => true,
            'message'    => 'Products Delete Deleted'
        ]);
    }



    public function apiProductsOut()
{
    // წამოვიღოთ მონაცემები პროდუქტთან და კლიენტთან ერთად
    $productOrder = \App\Models\Product_Order::with(['product', 'customer'])->get();

    $data = $productOrder->map(function ($item) {
        $exportPdfUrl = route('exportPDF.productOrder', ['id' => $item->id]);
        return [
            'id'             => $item->id,
            // დარწმუნდი, რომ ბაზაში სვეტს ჰქვია 'name' ან 'nama'
            'products_name'  => $item->product->name ?? $item->product->nama ?? 'N/A',
            'customer_name'  => $item->customer->name ?? $item->customer->nama ?? 'N/A',
            'qty'            => $item->qty,
            'tanggal'        => $item->tanggal,
            'action'         => '
                <a onclick="editForm('. $item->id .')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                '<a onclick="deleteData('. $item->id .')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>'.
        '<a href="'. $exportPdfUrl .'" class="btn btn-info btn-xs"><i class="fa fa-file-pdf-o"></i> Export Invoice</a>'
                ];
    });

    // ვაბრუნებთ სტანდარტულ JSON-ს, რომელსაც DataTable მარტივად წაიკითხავს
    return response()->json(['data' => $data]);
}

    public function exportProductOrderAll()
    {
        $product_Order = Product_Order::all();
        $pdf = PDF::loadView('product_Order.productOrderAllPDF',compact('product_Order'));
        return $pdf->download('product_out.pdf');
    }

    public function exportProductOrder($id)
{
    // ვიყენებთ პატარა o-ს ცვლადის სახელში
    $product_order = Product_Order::with(['product', 'customer'])->findOrFail($id);
    
    // გადაეცი ზუსტად ეს სახელი
    $pdf = PDF::loadView('product_order.productOrderPDF', compact('product_order'));
    
    return $pdf->download($product_order->id.'_invoice.pdf');
}

    public function exportExcel()
    {
        return (new ExportProdukOrder)->download('product_Order.xlsx');
    }
}
