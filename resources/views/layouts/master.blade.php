<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Warehouse</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome 6 --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- DataTables Bootstrap 5 --}}
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    {{-- SweetAlert2 --}}
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('upload/favicon.png') }}">

    <style>
        :root {
            --sidebar-width: 240px;
            --sidebar-bg: #1a1f2e;
            --sidebar-hover: #252b3d;
            --sidebar-active: #2d7dd2;
            --sidebar-text: #a0aec0;
            --sidebar-text-active: #fff;
            --topbar-height: 56px;
            --topbar-bg: #fff;
            --body-bg: #f0f2f5;
            --accent: #2d7dd2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--body-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            font-size: 14px;
            color: #2d3748;
             overflow-x: hidden;
        }

        /* ── SIDEBAR ── */
        #sidebar {
            position: fixed;
            top: 0; left: 0; bottom: 0;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: transform 0.25s ease;
            overflow-y: auto;
        }

        #sidebar .sidebar-brand {
            padding: 18px 20px 14px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            flex-shrink: 0;
        }
        #sidebar .sidebar-brand span {
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
        }
        #sidebar .sidebar-brand small {
            display: block;
            font-size: 10px;
            color: var(--sidebar-text);
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .sidebar-user img {
            width: 36px; height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.15);
        }
        .sidebar-user .user-info .name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            line-height: 1.2;
        }
        .sidebar-user .user-info .status {
            font-size: 11px;
            color: #48bb78;
        }

        .sidebar-nav { padding: 10px 0; flex: 1; }

        .sidebar-nav .nav-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.3);
            padding: 12px 20px 4px;
        }

        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.15s;
        }
        .sidebar-nav a:hover {
            background: var(--sidebar-hover);
            color: #fff;
        }
        .sidebar-nav a.active {
            background: var(--sidebar-hover);
            color: var(--sidebar-text-active);
            border-left-color: var(--accent);
        }
        .sidebar-nav a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
            opacity: 0.8;
        }
        .sidebar-nav a.active i { opacity: 1; }

        /* ── TOPBAR ── */
        #topbar {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--topbar-height);
            background: var(--topbar-bg);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 999;
            gap: 12px;
        }

        #topbar .topbar-toggle {
            background: none;
            border: none;
            color: #718096;
            font-size: 18px;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: 6px;
            display: none;
        }
        #topbar .topbar-toggle:hover { background: #f7fafc; }

        #topbar .topbar-spacer { flex: 1; }

        #topbar .topbar-title {
            font-size: 15px;
            font-weight: 700;
            color: #2d3748;
            white-space: nowrap;
        }

        #topbar .topbar-user {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 6px 10px;
            border-radius: 8px;
            transition: background 0.15s;
        }
        #topbar .topbar-user:hover { background: #f7fafc; }
        #topbar .topbar-user img {
            width: 32px; height: 32px;
            border-radius: 50%;
            object-fit: cover;
        }
        #topbar .topbar-user .uname {
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
        }

        /* ── MAIN CONTENT ── */
        #main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--topbar-height);
            min-height: calc(100vh - var(--topbar-height));
        }

        /* ── OVERLAY (mobile) ── */
        #sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        /* ── CARDS / BOXES ── */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #edf2f7;
            border-radius: 10px 10px 0 0 !important;
            padding: 14px 20px;
            font-weight: 600;
            font-size: 15px;
        }
        .card-body { padding: 20px; }

        /* ── DATATABLES ── */
        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 13px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
        }

        /* ── BUTTONS ── */
        .btn-xs { padding: 2px 8px; font-size: 12px; }

        /* ── BADGES (Bootstrap 3 label → Bootstrap 5 badge) ── */
        .badge { font-weight: 600; }

        /* ── FOOTER ── */
        #footer {
            margin-left: var(--sidebar-width);
            padding: 14px 24px;
            background: #fff;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #a0aec0;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.open {
                transform: translateX(0);
            }
            #sidebar-overlay.show { display: block; }
            #topbar, #main-content, #footer {
                left: 0;
                margin-left: 0;
                width: 100%;
            }
            #topbar .topbar-toggle { display: block; }

            /* მოდალები full-screen small-ზე უკეთ გამოიყურება */
            .modal-dialog:not(.modal-sm):not(.modal-dialog-centered) {
                margin: 0.5rem;
            }

            /* DataTables filter/length კომპაქტური */
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter {
                text-align: left;
                margin-bottom: 6px;
            }

            /* btn-xs უფრო კარგი touch target */
            .btn-xs {
                padding: 4px 10px;
                font-size: 12px;
            }
        }

        /* ── MODALS Bootstrap 5 ── */
        .modal-header { border-bottom: 1px solid #edf2f7; }
        .modal-footer { border-top: 1px solid #edf2f7; }

        /* ════════════════════════════════════════════
           GLOBAL MODULE DESIGN SYSTEM
        ════════════════════════════════════════════ */

        /* Design tokens */
        :root {
            --radius-xl: 18px;
            --radius-lg: 14px;
            --radius-md: 10px;
            --radius-sm: 7px;
            --shadow-xs: 0 1px 3px rgba(0,0,0,.05);
            --shadow-sm: 0 2px 8px rgba(0,0,0,.07);
            --shadow-md: 0 6px 20px rgba(0,0,0,.10);
            --shadow-hover: 0 10px 28px rgba(0,0,0,.13);
            --ease: cubic-bezier(.4,0,.2,1);
            --page-pad: clamp(12px, 3vw, 28px);
        }

        /* Page wrapper */
        .mod-wrap {
            padding: var(--page-pad);
            padding-bottom: 40px;
        }

        /* Page header */
        .mod-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }
        .mod-title {
            font-size: clamp(17px, 2.5vw, 22px);
            font-weight: 800;
            color: #1e293b;
            margin: 0 0 3px;
            letter-spacing: -.3px;
            line-height: 1.2;
        }
        .mod-subtitle {
            font-size: 12px;
            color: #94a3b8;
            margin: 0;
            font-weight: 500;
        }
        .mod-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            flex-shrink: 0;
        }

        /* Main content card */
        .mod-card {
            background: #fff;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(0,0,0,.045);
            overflow: hidden;
        }

        /* Toolbar inside card */
        .mod-toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            padding: 14px 18px;
            border-bottom: 1px solid #f1f5f9;
            background: #fafbfd;
        }
        .mod-toolbar .form-control,
        .mod-toolbar .form-select {
            border-radius: var(--radius-sm) !important;
            border: 1px solid #e2e8f0 !important;
            font-size: 13px !important;
            background: #fff;
            box-shadow: none !important;
            transition: border-color .15s;
        }
        .mod-toolbar .form-control:focus,
        .mod-toolbar .form-select:focus {
            border-color: var(--accent) !important;
            outline: none !important;
        }
        .mod-toolbar-search {
            flex: 1 1 160px;
            min-width: 120px;
            max-width: 260px;
            position: relative;
        }
        .mod-toolbar-search .search-icon {
            position: absolute;
            left: 10px; top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 12px;
            pointer-events: none;
        }
        .mod-toolbar-search .form-control {
            padding-left: 30px !important;
        }
        .mod-spacer { flex: 1; }

        /* Table inside mod-card */
        .mod-card .table-responsive { margin: 0; }
        .mod-card .table {
            margin: 0 !important;
            font-size: 13px;
        }
        .mod-card .table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .6px;
            border-bottom: 2px solid #e9edf3 !important;
            border-top: none !important;
            padding: 11px 14px;
            white-space: nowrap;
        }
        .mod-card .table tbody td {
            padding: 11px 14px;
            border-color: #f1f5f9 !important;
            vertical-align: middle;
        }
        .mod-card .table tbody tr:hover { background: #f8fafc; }
        .mod-card .table-striped tbody tr:nth-of-type(odd) { background: #fafbfd; }
        .mod-card .table-striped tbody tr:nth-of-type(odd):hover { background: #f1f5f9; }

        /* Pagination inside mod-card */
        .mod-card .dataTables_wrapper .dataTables_paginate {
            padding: 12px 18px;
        }
        .mod-card .dataTables_info {
            padding: 12px 18px;
            font-size: 12px;
            color: #94a3b8;
        }

        /* Modern buttons */
        .btn { border-radius: var(--radius-sm) !important; font-weight: 600; transition: all .15s var(--ease); }
        .btn-sm { font-size: 12px !important; padding: 6px 14px !important; }
        .btn-xs { font-size: 11px !important; padding: 3px 9px !important; border-radius: 5px !important; }
        .btn-success { background: #10b981 !important; border-color: #10b981 !important; }
        .btn-success:hover { background: #059669 !important; border-color: #059669 !important; }
        .btn-primary { background: #2d7dd2 !important; border-color: #2d7dd2 !important; }
        .btn-primary:hover { background: #1d6bbf !important; border-color: #1d6bbf !important; }
        .btn-danger { background: #ef4444 !important; border-color: #ef4444 !important; }
        .btn-danger:hover { background: #dc2626 !important; border-color: #dc2626 !important; }
        .btn-warning { background: #f59e0b !important; border-color: #f59e0b !important; color: #fff !important; }
        .btn-warning:hover { background: #d97706 !important; border-color: #d97706 !important; }
        .btn-info { background: #0ea5e9 !important; border-color: #0ea5e9 !important; color: #fff !important; }
        .btn-info:hover { background: #0284c7 !important; border-color: #0284c7 !important; }
        .btn-secondary { background: #64748b !important; border-color: #64748b !important; }
        .btn-secondary:hover { background: #475569 !important; border-color: #475569 !important; }
        .btn-outline-secondary { color: #64748b !important; border-color: #e2e8f0 !important; background: #fff !important; }
        .btn-outline-secondary:hover { background: #f8fafc !important; border-color: #cbd5e1 !important; }

        /* Modals */
        .modal-content {
            border: none !important;
            border-radius: var(--radius-lg) !important;
            box-shadow: var(--shadow-md) !important;
            overflow: hidden;
        }
        .modal-header {
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 16px 22px !important;
            background: #fff;
        }
        .modal-title { font-size: 15px !important; font-weight: 700 !important; color: #1e293b; }
        .modal-body { padding: 20px 22px !important; }
        .modal-footer {
            border-top: 1px solid #f1f5f9 !important;
            padding: 12px 22px !important;
            background: #fafbfd;
        }

        /* Form controls globally */
        .form-control, .form-select {
            border-radius: var(--radius-sm) !important;
            border: 1.5px solid #e2e8f0 !important;
            font-size: 13px;
            transition: border-color .15s, box-shadow .15s;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--accent) !important;
            box-shadow: 0 0 0 3px rgba(45,125,210,.12) !important;
        }
        .form-label { font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px; }

        /* Badge/label consistency */
        .badge { border-radius: 6px !important; font-weight: 600; font-size: 11px; }

        /* Stat cards (warehouse) */
        .stat-card {
            background: #fff;
            border-radius: var(--radius-lg) !important;
            padding: 16px 18px !important;
            box-shadow: var(--shadow-xs) !important;
            border: 1px solid rgba(0,0,0,.05) !important;
            border-left: 4px solid var(--sc-color, #10b981) !important;
            transition: all .15s var(--ease);
        }
        .stat-card:hover { box-shadow: var(--shadow-sm) !important; transform: translateY(-1px); }
        .stat-card .val { font-size: 28px !important; font-weight: 800 !important; color: #1e293b !important; }
        .stat-card .lbl { font-size: 11px !important; color: #94a3b8 !important; text-transform: uppercase; letter-spacing: .6px; margin-top: 4px; }
        .stat-card.orange { --sc-color: #f59e0b; }
        .stat-card.blue   { --sc-color: #3b82f6; }
        .stat-card.red    { --sc-color: #ef4444; }
        .stat-card.purple { --sc-color: #8b5cf6; }

        /* Icon badge helper */
        .icon-wrap {
            width: 36px; height: 36px;
            border-radius: 9px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 15px; flex-shrink: 0;
        }

        /* Section separator */
        .mod-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 0;
        }

        @media(max-width:576px) {
            .mod-toolbar { padding: 10px 14px; gap: 6px; }
            .mod-card .table thead th,
            .mod-card .table tbody td { padding: 9px 10px; }
            .mod-actions { width: 100%; }
            .mod-header { margin-bottom: 14px; }
        }
    </style>

    @yield('top')
</head>
<body>

{{-- SIDEBAR --}}
<div id="sidebar-overlay" onclick="closeSidebar()"></div>
<nav id="sidebar">
    <div class="sidebar-brand">
        <span>🏭 ORIGINAL 100%</span>
        <small>Warehouse Management</small>
    </div>

    <div class="sidebar-user">
        <img src="{{ asset('user-profile.png') }}" alt="User">
        <div class="user-info">
            <div class="name">{{ Auth::user()->name }}</div>
            <div class="status"><i class="fa fa-circle" style="font-size:8px;"></i> Online</div>
        </div>
    </div>

    <div class="sidebar-nav">
        @include('layouts.sidebar')
    </div>
</nav>

{{-- TOPBAR --}}
<header id="topbar">
    <button class="topbar-toggle" onclick="toggleSidebar()">
        <i class="fa fa-bars"></i>
    </button>

    @hasSection('page_title')
        <div class="topbar-title">@yield('page_title')</div>
    @endif

    <div class="topbar-spacer"></div>

    <div class="dropdown">
        <div class="topbar-user dropdown-toggle" data-bs-toggle="dropdown">
            <img src="{{ asset('user-profile.png') }}" alt="User">
            <span class="uname">{{ Auth::user()->name }}</span>
        </div>
        <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px; border-radius:10px; border:none; box-shadow:0 8px 24px rgba(0,0,0,0.12);">
            <li>
                <a class="dropdown-item" href="{{ route('user.change-password') }}">
                    <i class="fa fa-key me-2 text-muted"></i> პაროლის შეცვლა
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa fa-right-from-bracket me-2"></i> გასვლა
                </a>
            </li>
        </ul>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
</header>

{{-- MAIN CONTENT --}}
<main id="main-content">
    @yield('content')
</main>

{{-- FOOTER --}}
<footer id="footer">
    <strong>&copy; {{ date('Y') }} Warehouse Management System</strong>
    <span class="float-end">Developed by Ghvedashvili</span>
</footer>

{{-- SCRIPTS --}}
{{-- jQuery (DataTables, Select2, Ajax-ისთვის) --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
{{-- Bootstrap 5 --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
{{-- DataTables + Bootstrap 5 --}}
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>


<div id="fb-root"></div>
<div id="fb-customer-chat" class="fb-customerchat"></div>

<script>
  var chatbox = document.getElementById('fb-customer-chat');
  chatbox.setAttribute("page_id", "196409790221137");
  chatbox.setAttribute("attribution", "biz_inbox");
  chatbox.setAttribute("theme_color", "#0084ff");
</script>

<script>
  window.fbAsyncInit = function () {
    FB.init({
      xfbml: true,
      version: 'v18.0'
    });
  };

  (function(d, s, id) {
    if (d.getElementById(id)) return;
    var js = d.createElement(s);
    js.id = id;
    js.src = "https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js";
    var fjs = d.getElementsByTagName(s)[0];
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));
</script>



<script>
// ── Bootstrap 3 modal API → Bootstrap 5 shim ──────────────────────────
(function($) {
    var _modal = $.fn.modal;
    $.fn.modal = function(option) {
        var $el = this;
        if (typeof option === 'string') {
            $el.each(function() {
                var el = this;
                var instance = bootstrap.Modal.getOrCreateInstance(el);
                if (option === 'show')   instance.show();
                if (option === 'hide')   instance.hide();
                if (option === 'toggle') instance.toggle();
                if (option === 'dispose') instance.dispose();
            });
        } else if (typeof option === 'object' || option === undefined) {
            $el.each(function() {
                var opts = $.extend({ backdrop: 'static', keyboard: false }, option || {});
                if ($(this).data('bs-backdrop') === 'static' ||
                    $(this).attr('data-backdrop') === 'static') {
                    opts.backdrop = 'static';
                }
                new bootstrap.Modal(this, opts);
            });
        }
        return $el;
    };

    $(document).on('show.bs.modal', '.modal', function() {
        $(this).trigger('show.bs.modal.bs3');
    });
}(jQuery));

// ── swal shim (SweetAlert2) ────────────────────────────────────────────
window.swal = function(titleOrObj, text, type) {
    var opts = {};
    if (typeof titleOrObj === 'object') {
        opts = $.extend({}, titleOrObj);
        if (opts.type && !opts.icon) { opts.icon = opts.type; delete opts.type; }
        if (opts.showCancelButton === undefined && opts.buttons === true) {
            opts.showCancelButton = true;
        }
    } else {
        opts = { title: titleOrObj, text: text, icon: type };
    }
    if (opts.timer && typeof opts.timer === 'string') {
        opts.timer = parseInt(opts.timer);
    }
    return Swal.fire(opts);
};

// ── Sidebar toggle ─────────────────────────────────────────────────────
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebar-overlay').classList.toggle('show');
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebar-overlay').classList.remove('show');
}

// ── Active sidebar link ────────────────────────────────────────────────
$(function() {
    var path = window.location.pathname;
    $('#sidebar .sidebar-nav a').each(function() {
        var href = $(this).attr('href');
        if (href && path.startsWith(href) && href !== '/') {
            $(this).addClass('active');
        } else if (href === path) {
            $(this).addClass('active');
        }
    });
});
</script>

@yield('bot')
</body>
</html>