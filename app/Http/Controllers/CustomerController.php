<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\City;
use App\Exports\ExportCustomers;
use App\Imports\CustomersImport;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class CustomerController extends Controller
{
    public function __construct()
    {
        // Middleware როლებისთვის
        $this->middleware('role:admin,staff,sale_operator');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cities = City::all();
        return view('customers.index', compact('cities'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $this->validate($request, [
        'name'    => 'required|string|max:255',
        'city_id' => 'required|exists:cities,id',
        'address' => 'required|string|max:255',
        'email'   => 'nullable|email|unique:customers,email',
        'tel'     => 'required|unique:customers,tel',
    ]);

    $customer = Customer::create($request->all());
    $customer->load('city');

    return response()->json([
        'success'         => true,
        'message'         => 'Customer Created Successfully!',
        'id'              => $customer->id,
        'name'            => $customer->name,
        'tel'             => $customer->tel,
        'address'         => $customer->address,
         'city_id'         => $customer->city_id,
        'city_name'       => $customer->city->name ?? '',
        'alternative_tel' => $customer->alternative_tel,
        'comment'         => $customer->comment,
    ]);
}

public function update(Request $request, $id)
{
    $this->validate($request, [
        'name'    => 'required|string|max:255',
        'city_id' => 'required|exists:cities,id',
        'address' => 'required|string|max:255',
        'email'   => 'nullable|email|unique:customers,email,' . $id,
        'tel'     => 'required|unique:customers,tel,' . $id,
    ]);

    $customer = Customer::findOrFail($id); // პირველ ეს
    $customer->update($request->all());    // მერე ეს

    return response()->json([
        'success' => true,
        'message' => 'Customer Updated Successfully!'
    ]);
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        Customer::destroy($id);

        return response()->json([
            'success' => true,
            'message' => 'Customer Deleted Successfully!'
        ]);
    }

    /**
     * Yajra DataTables API
     */
    public function apiCustomers(Request $request)
{
    // ვიყენებთ with('city') ოპტიმიზაციისთვის
    $customers = Customer::leftJoin('cities', 'customers.city_id', '=', 'cities.id')
        ->select('customers.*', 'cities.name as city_name');

    // ფილტრაცია ქალაქის მიხედვით
    if ($request->has('city_id') && $request->city_id != "") {
        $customers->where('customers.city_id', $request->city_id);
    }

    return Datatables::of($customers->get())
        ->addColumn('contact_info', function($customer){
            return "<b>Tel:</b> {$customer->tel}<br>" . 
                   ($customer->alternative_tel ? "<b>Alt:</b> {$customer->alternative_tel}" : "");
        })
        ->addColumn('action', function($customer){
            return '<center>'.
                   '<a onclick="editForm('. $customer->id .')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                   '<a onclick="deleteData('. $customer->id .')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>'.
                   '</center>';
        })
        ->rawColumns(['contact_info', 'action'])
        ->make(true);
}

    /**
     * Import Customers from Excel
     */
    public function ImportExcel(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:xls,xlsx'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            Excel::import(new CustomersImport, $file);
            return redirect()->back()->with(['success' => 'Data imported successfully!']);
        }

        return redirect()->back()->with(['error' => 'Please choose a valid file!']);
    }

    /**
     * Export all customers to PDF
     */
    public function exportCustomersAll()
    {
        $customers = Customer::all();
        $pdf = PDF::loadView('customers.CustomersAllPDF', compact('customers'));
        return $pdf->download('customers_list.pdf');
    }

    /**
     * Export customers to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new ExportCustomers, 'customers.xlsx');
    }
}