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
        $this->middleware('role:admin,staff');
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
            'email'   => 'required|email|unique:customers,email',
            'tel'     => 'required|unique:customers,tel',
        ]);

        Customer::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer Created Successfully!'
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
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name'    => 'required|string|max:255',
            'city_id' => 'required|exists:cities,id',
            'address' => 'required|string|max:255',
            'email'   => 'required|email|unique:customers,email,' . $id,
            'tel'     => 'required|unique:customers,tel,' . $id,
        ]);

        $customer = Customer::findOrFail($id);
        $customer->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Customer Updated Successfully!'
        ]);
    }

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
    public function apiCustomers()
    {
        $customers = Customer::all();

        return Datatables::of($customers)
            ->addColumn('action', function($customer){
                return '<a onclick="editForm('. $customer->id .')" class="btn btn-primary btn-xs"><i class="fa fa-edit"></i> Edit</a> ' .
                       '<a onclick="deleteData('. $customer->id .')" class="btn btn-danger btn-xs"><i class="fa fa-trash"></i> Delete</a>';
            })
            ->rawColumns(['action'])
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