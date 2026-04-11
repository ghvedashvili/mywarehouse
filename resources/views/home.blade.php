@extends('layouts.master')

@section('top')
<style>
    .dashboard-card {
        transition: all 0.3s ease;
        border: none;
        border-radius: 12px;
        color: white;
        overflow: hidden;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }
    .card-icon {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 2.5rem;
        opacity: 0.2;
    }
    .dashboard-card h3 { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
    .dashboard-card p { font-size: 0.9rem; margin-bottom: 0; opacity: 0.8; }
</style>
@endsection

@section('content')
<div class="container-fluid py-4 overflow-hidden">
    <div class="row g-3">
        
        <div class="col-12 col-sm-6 col-xl-3">
            <a href="/user" class="text-decoration-none">
                <div class="card dashboard-card bg-info h-100 shadow-sm">
                    <div class="card-body position-relative">
                        <h3>{{ \App\Models\User::count() }}</h3>
                        <p>System Users</p>
                        <div class="card-icon"><i class="fa fa-user-secret"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('categories.index') }}" class="text-decoration-none">
                <div class="card dashboard-card bg-success h-100 shadow-sm">
                    <div class="card-body position-relative">
                        <h3>{{ \App\Models\Category::count() }}</h3>
                        <p>Categories</p>
                        <div class="card-icon"><i class="fa fa-list"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('products.index') }}" class="text-decoration-none">
                <div class="card dashboard-card bg-warning h-100 shadow-sm text-dark">
                    <div class="card-body position-relative">
                        <h3>{{ \App\Models\Product::count() }}</h3>
                        <p>Products</p>
                        <div class="card-icon"><i class="fa fa-cubes"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('customers.index') }}" class="text-decoration-none">
                <div class="card dashboard-card bg-danger h-100 shadow-sm">
                    <div class="card-body position-relative">
                        <h3>{{ \App\Models\Customer::count() }}</h3>
                        <p>Customers</p>
                        <div class="card-icon"><i class="fa fa-users"></i></div>
                    </div>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <a href="{{ route('productsOut.index') }}" class="text-decoration-none">
                <div class="card dashboard-card bg-primary h-100 shadow-sm">
                    <div class="card-body position-relative">
                        <h3>{{ \App\Models\Product_Order::count() }}</h3>
                        <p>Total Outgoing</p>
                        <div class="card-icon"><i class="fa fa-cart-arrow-down"></i></div>
                    </div>
                </div>
            </a>
        </div>

    </div>
</div>
@endsection