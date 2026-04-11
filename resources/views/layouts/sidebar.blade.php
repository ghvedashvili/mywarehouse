<div class="d-flex flex-column h-100">
   

    <div class="sidebar-nav flex-grow-1">
        <style>
            .sidebar-link {
                display: flex;
                align-items: center;
                padding: 12px 25px;
                color: #a0a5b1;
                text-decoration: none;
                transition: 0.3s;
            }
            .sidebar-link:hover, .sidebar-link.active {
                background: #252b3d;
                color: #fff;
            }
            .sidebar-link i { margin-right: 15px; width: 20px; text-align: center; }
            .nav-label { padding: 15px 25px 5px; font-size: 11px; color: #5a6170; text-transform: uppercase; font-weight: bold; }
        </style>

        <a href="{{ url('/home') }}" class="sidebar-link {{ request()->is('home') ? 'active' : '' }}">
            <i class="fa fa-gauge"></i> Dashboard
        </a>
        <a href="{{ route('categories.index') }}" class="sidebar-link {{ request()->is('categories*') ? 'active' : '' }}">
            <i class="fa fa-list"></i> Category
        </a>
        <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->is('products*') ? 'active' : '' }}">
            <i class="fa fa-cubes"></i> Product
        </a>
        <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->is('customers*') ? 'active' : '' }}">
            <i class="fa fa-users"></i> Customer
        </a>

        <div class="nav-label">Operations</div>
        <a href="{{ route('productsOut.index') }}" class="sidebar-link {{ request()->is('productsOut*') ? 'active' : '' }}">
            <i class="fa fa-right-from-bracket"></i> გაყიდვები
        </a>
        <a href="{{ route('warehouse.index') }}" class="sidebar-link {{ request()->is('warehouse*') ? 'active' : '' }}">
            <i class="fa fa-warehouse"></i> საწყობი
        </a>
        <li>
    <a href="{{ route('warehouse.logs') }}">
        <i class="fa fa-history"></i>
        <span>საწყობის ლოგი</span>
    </a>
</li>
        <a href="{{ route('purchases.index') }}" class="sidebar-link {{ request()->is('purchases*') ? 'active' : '' }}">
            <i class="fa fa-cart-shopping"></i> შესყიდვები
        </a>

        @if(Auth::user()->role === 'admin')
            <div class="nav-label">Admin Panel</div>
            <a href="{{ route('finance.index') }}" class="sidebar-link {{ request()->is('finance*') ? 'active' : '' }}">
                <i class="fa fa-chart-line"></i> 💰 ფინანსები
            </a>
            <a href="{{ route('user.index') }}" class="sidebar-link {{ request()->is('user*') ? 'active' : '' }}">
                <i class="fa fa-user-shield"></i> System Users
            </a>
        @endif
    </div>
</div>