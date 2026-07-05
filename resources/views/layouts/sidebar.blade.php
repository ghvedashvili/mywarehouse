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

        @php
            $role = Auth::user()->role;
            $can  = fn(string $page) => \App\Models\RolePermission::check($role, $page);
        @endphp

        <a href="{{ url('/home') }}" class="sidebar-link {{ request()->is('home') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-gauge"></i> მთავარი
        </a>

        @if($can('categories'))
        <a href="{{ route('categories.index') }}" class="sidebar-link {{ request()->is('categories*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-tags"></i> კატეგორიები
        </a>
        @endif

        @if($can('brands'))
        <a href="{{ route('brands.index') }}" class="sidebar-link {{ request()->is('brands*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-copyright"></i> ბრენდები
        </a>
        @endif

        @if($can('products'))
        <a href="{{ route('products.index') }}" class="sidebar-link {{ request()->is('products') || request()->is('products/*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-cubes"></i> პროდუქტები
        </a>
        @endif

        @if($can('product_bundles'))
        <a href="{{ route('product-bundles.index') }}" class="sidebar-link {{ request()->is('product-bundles*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-object-group"></i> კომპლექტები
        </a>
        @endif

        @if($can('customers'))
        <a href="{{ route('customers.index') }}" class="sidebar-link {{ request()->is('customers*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-users"></i> მომხმარებლები
        </a>
        @endif

        <div class="nav-label">Operations</div>

        @if($can('sales'))
        <a href="{{ route('productsOut.index') }}" class="sidebar-link {{ request()->is('productsOut*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-right-from-bracket"></i> გაყიდვები
        </a>
        @endif

        @if($can('warehouse'))
        <a href="{{ route('warehouse.index') }}" class="sidebar-link {{ request()->is('warehouse*') && !request()->is('warehouse/logs*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-warehouse"></i> საწყობი
        </a>
        @endif

        @if($can('warehouse_logs'))
        <a href="{{ route('warehouse.logs') }}" class="sidebar-link {{ request()->is('warehouse/logs*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-history"></i> საწყობის ლოგი
        </a>
        @endif

        @if($can('purchases'))
        <a href="{{ route('purchases.index') }}" class="sidebar-link {{ request()->is('purchases*') ? 'active' : '' }}" onclick="closeSidebar()">
            <i class="fa fa-cart-shopping"></i> შესყიდვები
        </a>
        @endif

        @if($role === 'admin')
            <div class="nav-label">Admin Panel</div>
            <a href="{{ route('finance.index') }}" class="sidebar-link {{ request()->is('finance*') ? 'active' : '' }}" onclick="closeSidebar()">
                <i class="fa fa-chart-line"></i> 💰 ფინანსები
            </a>
            <a href="{{ route('couriers.index') }}" class="sidebar-link {{ request()->is('couriers*') ? 'active' : '' }}" onclick="closeSidebar()">
                <i class="fa fa-truck"></i> კურიერები
            </a>
            <a href="{{ route('user.index') }}" class="sidebar-link {{ request()->is('user*') ? 'active' : '' }}" onclick="closeSidebar()">
                <i class="fa fa-user-shield"></i> თანამშრომლები
            </a>
            <a href="{{ route('salary-policy.index') }}" class="sidebar-link {{ request()->is('salary-policy*') ? 'active' : '' }}" onclick="closeSidebar()">
                <i class="fa fa-sliders"></i> სახელფასო პოლიტიკა
            </a>
            <a href="{{ route('roles.index') }}" class="sidebar-link {{ request()->is('roles*') ? 'active' : '' }}" onclick="closeSidebar()">
                <i class="fa fa-shield-halved"></i> როლების უფლებები
            </a>
            <a href="{{ route('diagnostic.index') }}" class="sidebar-link {{ request()->is('diagnostic*') ? 'active' : '' }}" onclick="closeSidebar()">
                <i class="fa fa-stethoscope"></i> დიაგნოსტიკა
            </a>
        @endif
    </div>
</div>
