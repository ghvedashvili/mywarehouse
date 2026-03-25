@extends('layouts.master')

@section('top')
@endsection

@section('content')
<div class="row">
 
    <div class="col-lg-3 col-xs-6">
        <a href="/user" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-aqua">
                <div class="inner">
                    <h3>{{ \App\Models\User::count() }}</h3>
                    <p>System Users</p>
                </div>
                <div class="icon"><i class="fa fa-user-secret"></i></div>
            </div>
        </a>
    </div>
 
    <div class="col-lg-3 col-xs-6">
        <a href="{{ route('categories.index') }}" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{ \App\Models\Category::count() }}</h3>
                    <p>Category</p>
                </div>
                <div class="icon"><i class="fa fa-list"></i></div>
            </div>
        </a>
    </div>
 
    <div class="col-lg-3 col-xs-6">
        <a href="{{ route('products.index') }}" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-yellow">
                <div class="inner">
                    <h3>{{ \App\Models\Product::count() }}</h3>
                    <p>Product</p>
                </div>
                <div class="icon"><i class="fa fa-cubes"></i></div>
            </div>
        </a>
    </div>
 
    <div class="col-lg-3 col-xs-6">
        <a href="{{ route('customers.index') }}" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-red">
                <div class="inner">
                    <h3>{{ \App\Models\Customer::count() }}</h3>
                    <p>Customer</p>
                </div>
                <div class="icon"><i class="fa fa-users"></i></div>
            </div>
        </a>
    </div>
 
</div>
 
<div class="row">
 
    <div class="col-lg-3 col-xs-6">
        <a href="{{ route('suppliers.index') }}" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>{{ \App\Models\Supplier::count() }}</h3>
                    <p>Supplier</p>
                </div>
                <div class="icon"><i class="fa fa-signal"></i></div>
            </div>
        </a>
    </div>
 
    <div class="col-lg-3 col-xs-6">
        <a href="{{ route('productsIn.index') }}" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-maroon">
                <div class="inner">
                    <h3>{{ \App\Models\Product_Masuk::count() }}</h3>
                    <p>Total Purchase</p>
                </div>
                <div class="icon"><i class="fa fa-cart-plus"></i></div>
            </div>
        </a>
    </div>
 
    <div class="col-lg-3 col-xs-6">
        <a href="{{ route('productsOut.index') }}" style="color:inherit; text-decoration:none;">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ \App\Models\Product_Order::count() }}</h3>
                    <p>Total Outgoing</p>
                </div>
                <div class="icon"><i class="fa fa-minus"></i></div>
            </div>
        </a>
    </div>
 
</div>
@endsection

@section('top')
@endsection
