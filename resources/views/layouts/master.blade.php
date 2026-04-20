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

<script>
// ── Bootstrap 3 modal API → Bootstrap 5 shim ──────────────────────────
// ამით ძველი $.fn.modal('show') კოდი კვლავ მუშაობს
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

    // Bootstrap 3 events → Bootstrap 5 events shim
    $(document).on('show.bs.modal', '.modal', function() {
        $(this).trigger('show.bs.modal.bs3');
    });
    $(document).on('hidden.bs.modal', '.modal', function() {
        // Bootstrap 5-ში backdrop ავტომატურად იშლება
        // body-ს overflow ავტომატურად ბრუნდება
    });
}(jQuery));

// ── swal shim (SweetAlert2) ────────────────────────────────────────────
// ძველი swal({type:...}) → swal({icon:...})
window.swal = function(titleOrObj, text, type) {
    var opts = {};
    if (typeof titleOrObj === 'object') {
        opts = $.extend({}, titleOrObj);
        // type → icon
        if (opts.type && !opts.icon) { opts.icon = opts.type; delete opts.type; }
        // buttons → showCancelButton
        if (opts.showCancelButton === undefined && opts.buttons === true) {
            opts.showCancelButton = true;
        }
    } else {
        opts = { title: titleOrObj, text: text, icon: type };
    }
    // timer-ის string → number
    if (opts.timer && typeof opts.timer === 'string') {
        opts.timer = parseInt(opts.timer);
    }
    // willDelete / willRestore pattern → .then(result => result.isConfirmed)
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

<!-- Facebook Messenger Chat -->
<div id="fb-root"></div>
<script>
window.fbAsyncInit = function() {
    FB.init({ appId: '2257873808285058', xfbml: true, version: 'v18.0' });
};
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
</script>
<div class="fb-customerchat"
     attribution="biz_inbox"
     page_id="196409790221137">
</div>

</body>
</html>