<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Warehouse</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2d7dd2">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Warehouse">
    <link rel="apple-touch-icon" href="{{ asset('upload/favicon.png') }}">

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    {{-- Font Awesome 6 --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    {{-- DataTables Bootstrap 5 --}}
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    {{-- DataTables Responsive --}}
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
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
            padding-top: env(safe-area-inset-top, 0px);
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
            height: calc(var(--topbar-height) + env(safe-area-inset-top, 0px));
            padding-top: env(safe-area-inset-top, 0px);
            background: var(--topbar-bg);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            padding-left: 24px;
            padding-right: 24px;
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
            padding-top: calc(var(--topbar-height) + env(safe-area-inset-top, 0px));
            min-height: calc(100vh - var(--topbar-height) - env(safe-area-inset-top, 0px));
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
            #sidebar { transform: translateX(-100%); }
            #sidebar.open { transform: translateX(0); }
            #sidebar-overlay.show { display: block; }
            #topbar, #main-content, #footer {
                left: 0; margin-left: 0; width: 100%;
            }
            #topbar .topbar-toggle { display: block; }
            .modal-dialog:not(.modal-sm):not(.modal-dialog-centered) { margin: 0.5rem; }
            .dataTables_wrapper .dataTables_length,
            .dataTables_wrapper .dataTables_filter { text-align: left; margin-bottom: 6px; }
            .btn-xs { padding: 4px 10px; font-size: 12px; }
        }

        /* ── Topbar search (hidden on desktop, pages inject via blade sections) ── */
        .mob-topbar-search { display: none; }
        .mob-drawer-backdrop { display: none; }
        .mob-drawer { display: none; }
        .mob-ts-filter-btn { display: none; }
        @keyframes mobFadeBackdrop { from { opacity:0; } to { opacity:1; } }
        @keyframes mobSlideDownPanel { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }

        /* ════════════════════════════════════════════
           MOBILE CARD VIEW  (≤767px)
        ════════════════════════════════════════════ */
        @media (max-width: 767px) {

            /* Prevent iOS auto-zoom on input focus (font-size must be ≥16px) */
            input, select, textarea,
            .form-control, .form-select,
            .select2-search__field {
                font-size: 16px !important;
            }

            /* Page padding */
            .mod-wrap { padding: 12px 10px 60px; }
            .mod-header .mod-title,
            .mod-header .mod-subtitle,
            .mod-header > div:first-child { display: none !important; }
            .mod-toolbar { padding: 8px 10px; gap: 6px; }

            /* Hide table wrapper overflow so cards don't scroll sideways */
            .mod-card .table-responsive { overflow: visible !important; }

            /* ── thead hidden ── */
            .mod-card table.dataTable thead,
            .po-table-card table.dataTable thead { display: none !important; }

            /* ── tbody: no table layout ── */
            .mod-card table.dataTable,
            .mod-card table.dataTable tbody,
            .po-table-card table.dataTable,
            .po-table-card table.dataTable tbody { display: block !important; width: 100% !important; }

            /* ── Each row = card ── */
            .mod-card table.dataTable tbody tr,
            .po-table-card table.dataTable tbody tr {
                display: block !important;
                background: #fff !important;
                border-radius: 12px !important;
                margin: 0 0 10px !important;
                box-shadow: 0 2px 10px rgba(0,0,0,.07) !important;
                border: 1px solid #e9edf3 !important;
                overflow: hidden;
                transition: box-shadow .15s;
            }
            .mod-card table.dataTable tbody tr:hover {
                box-shadow: 0 4px 16px rgba(0,0,0,.10) !important;
                background: #fff !important;
            }


            /* ── Each cell = row inside card ── */
            .mod-card table.dataTable tbody td {
                display: flex !important;
                align-items: center;
                justify-content: space-between;
                padding: 9px 14px !important;
                border: none !important;
                border-bottom: 1px solid #f1f5f9 !important;
                font-size: 13px;
                min-height: 40px;
                vertical-align: middle !important;
            }
            .mod-card table.dataTable tbody td:last-child {
                border-bottom: none !important;
            }

            /* ── data-label as left side label ── */
            .mod-card table.dataTable tbody td[data-label]::before {
                content: attr(data-label);
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .5px;
                flex-shrink: 0;
                margin-right: 12px;
                min-width: 80px;
            }

            /* ── Actions cell: full width, centered buttons ── */
            .mod-card table.dataTable tbody td.td-actions {
                justify-content: flex-end;
                gap: 6px;
                padding: 10px 14px !important;
                background: #fafbfd;
                flex-wrap: wrap;
            }
            .mod-card table.dataTable tbody td.td-actions::before { display: none; }

            /* ── DataTables Responsive child row ── */
            .mod-card table.dataTable tbody tr.child td {
                background: #f8fafc !important;
                border-radius: 0 0 12px 12px !important;
                padding: 0 !important;
                border: none !important;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details {
                margin: 0 !important;
                padding: 6px 14px 10px !important;
                list-style: none;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details li {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid #edf2f7;
                font-size: 13px;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details li:last-child {
                border-bottom: none;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details li span.dtr-title {
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .5px;
                min-width: 80px;
                flex-shrink: 0;
            }

            /* ── Responsive expand control ── */
            table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
            table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
                background-color: var(--accent) !important;
                border-color: var(--accent) !important;
            }

            /* ── Pagination compact ── */
            .mod-card .dataTables_wrapper .dataTables_paginate {
                padding: 8px 10px !important;
            }
            .mod-card .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 4px 8px !important;
                font-size: 12px !important;
            }
            .mod-card .dataTables_info { padding: 6px 10px !important; font-size: 11px !important; }

            /* ── Modals: bottom-sheet style ── */
            .modal { align-items: flex-end !important; }
            .modal-dialog {
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .modal-content {
                border-radius: 22px 22px 0 0 !important;
                min-height: unset !important;
                max-height: 92vh;
                overflow-y: auto;
                padding-bottom: env(safe-area-inset-bottom, 12px);
            }
            /* Centered / small dialogs stay centered */
            .modal-dialog.modal-dialog-centered {
                align-self: center !important;
                margin: 0 16px !important;
                max-width: calc(100% - 32px) !important;
                width: auto !important;
            }
            .modal-dialog.modal-dialog-centered .modal-content {
                border-radius: 20px !important;
                max-height: 90vh;
                overflow-y: auto;
            }
            .modal-dialog.modal-sm {
                align-self: center !important;
                margin: 0 auto !important;
                max-width: calc(100% - 40px) !important;
                width: auto !important;
            }
            .modal-dialog.modal-sm .modal-content {
                border-radius: 18px !important;
                min-height: unset !important;
            }

            /* ── Buttons bigger touch targets ── */
            .btn-sm { padding: 7px 14px !important; font-size: 13px !important; }
            .btn-xs { padding: 5px 10px !important; font-size: 12px !important; }

            /* ── Topbar title shorter ── */
            #topbar .topbar-title { font-size: 14px; }
            #topbar .uname { max-width: 72px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }

            /* ── Footer mobile ── */
            #footer { margin-left: 0; font-size: 11px; text-align: center; }
            #footer .float-end { float: none !important; display: block; }

            /* ── Topbar injected search ── */
            .mob-topbar-search { display: flex; flex: 1; align-items: center; min-width: 0; }
            .mob-ts-bar {
                flex: 1; min-width: 0; display: flex; align-items: center; gap: 8px;
                background: #f0f2f5; border-radius: 10px; padding: 8px 6px 8px 12px;
                border: 1.5px solid transparent; transition: all .15s;
            }
            .mob-ts-bar:focus-within { border-color: #2d7dd2; background: #fff; }
            .mob-ts-bar > i { font-size: 12px; color: #94a3b8; flex-shrink: 0; }
            .mob-ts-bar input {
                flex: 1; min-width: 0; background: none; border: none; outline: none;
                font-size: 15px !important; color: #1e293b; font-family: inherit;
            }
            .mob-ts-bar input::placeholder { color: #94a3b8; }
            /* Filter button sits INSIDE the search bar on the right */
            .mob-ts-filter-btn {
                position: relative; width: 34px; height: 34px; flex-shrink: 0;
                border-radius: 8px; border: none; background: transparent; color: #94a3b8;
                display: flex; align-items: center; justify-content: center;
                cursor: pointer; font-family: inherit; transition: all .15s; font-size: 14px;
            }
            .mob-ts-filter-btn.active,
            .mob-ts-filter-btn.has-active { color: #2d7dd2; background: rgba(45,125,210,.1); }
            .mob-ts-filter-badge {
                position: absolute; top: -4px; right: -4px;
                background: #2d7dd2; color: #fff; border-radius: 7px;
                font-size: 9px; font-weight: 700; padding: 0 3px;
                min-width: 14px; height: 14px; line-height: 14px; text-align: center;
                border: 2px solid #f0f2f5;
                display: none; align-items: center; justify-content: center;
            }
            .mob-ts-filter-btn.has-active .mob-ts-filter-badge { display: flex; }
            .mob-ts-bar:focus-within .mob-ts-filter-badge { border-color: #fff; }
            /* collapse title + spacer when search slot is filled */
            #topbar:has(.mob-topbar-search) .topbar-title { display: none !important; }
            #topbar:has(.mob-topbar-search) .topbar-spacer { flex: 0 !important; min-width: 0 !important; }

            /* ── Generic mobile top-down panel ── */
            .mob-drawer-backdrop {
                display: none; position: fixed;
                top: var(--topbar-height, 56px); left: 0; right: 0; bottom: 0;
                z-index: 8997; background: rgba(0,0,0,.28);
            }
            .mob-drawer-backdrop.show { display: block !important; animation: mobFadeBackdrop .18s ease; }
            .mob-drawer {
                display: none !important; flex-direction: column;
                position: fixed; top: var(--topbar-height, 56px); left: 0; right: 0; z-index: 8998;
                background: #fff; border-radius: 0 0 18px 18px;
                box-shadow: 0 6px 24px rgba(0,0,0,.14);
                max-height: calc(80vh - var(--topbar-height, 56px)); overflow-y: auto;
            }
            .mob-drawer.open { display: flex !important; animation: mobSlideDownPanel .2s ease; }
            .mob-drawer-header {
                display: flex; align-items: center; justify-content: space-between;
                padding: 12px 16px 10px; border-bottom: 1px solid #f1f5f9; flex-shrink: 0;
            }
            .mob-drawer-title { font-size: 14px; font-weight: 700; color: #1e293b; }
            .mob-drawer-close {
                width: 28px; height: 28px; border-radius: 50%; border: none;
                background: #f1f5f9; color: #64748b; font-size: 13px;
                cursor: pointer; display: flex; align-items: center; justify-content: center; font-family: inherit;
            }
            .mob-drawer-body { display: flex; flex-direction: column; padding: 12px 16px 16px; gap: 9px; }
            .mob-drawer-label { font-size: 11.5px; font-weight: 600; color: #64748b; margin-bottom: 2px; }
            .mob-drawer-body select, .mob-drawer-body .form-select {
                width: 100% !important; height: 42px !important; font-size: 13.5px !important;
                border-radius: 10px !important; border: 1.5px solid #e2e8f0 !important; padding: 0 10px !important;
            }
            .mob-drawer-row {
                display: flex; align-items: center; justify-content: space-between;
                padding: 9px 0; border-top: 1px solid #f1f5f9; font-size: 13px; color: #475569;
            }
            .mod-card table.dataTable tbody td:last-child {
                border-bottom: none !important;
            }

            /* ── data-label as left side label ── */
            .mod-card table.dataTable tbody td[data-label]::before {
                content: attr(data-label);
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .5px;
                flex-shrink: 0;
                margin-right: 12px;
                min-width: 80px;
            }

            /* ── Actions cell: full width, centered buttons ── */
            .mod-card table.dataTable tbody td.td-actions {
                justify-content: flex-end;
                gap: 6px;
                padding: 10px 14px !important;
                background: #fafbfd;
                flex-wrap: wrap;
            }
            .mod-card table.dataTable tbody td.td-actions::before { display: none; }

            /* ── DataTables Responsive child row ── */
            .mod-card table.dataTable tbody tr.child td {
                background: #f8fafc !important;
                border-radius: 0 0 12px 12px !important;
                padding: 0 !important;
                border: none !important;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details {
                margin: 0 !important;
                padding: 6px 14px 10px !important;
                list-style: none;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details li {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid #edf2f7;
                font-size: 13px;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details li:last-child {
                border-bottom: none;
            }
            .mod-card table.dataTable tbody tr.child td ul.dtr-details li span.dtr-title {
                font-size: 10px;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: .5px;
                min-width: 80px;
                flex-shrink: 0;
            }

            /* ── Responsive expand control ── */
            table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
            table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
                background-color: var(--accent) !important;
                border-color: var(--accent) !important;
            }

            /* ── Pagination compact ── */
            .mod-card .dataTables_wrapper .dataTables_paginate {
                padding: 8px 10px !important;
            }
            .mod-card .dataTables_wrapper .dataTables_paginate .paginate_button {
                padding: 4px 8px !important;
                font-size: 12px !important;
            }
            .mod-card .dataTables_info { padding: 6px 10px !important; font-size: 11px !important; }

            /* ── Modals: bottom-sheet style ── */
            .modal { align-items: flex-end !important; }
            .modal-dialog {
                margin: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }
            .modal-content {
                border-radius: 22px 22px 0 0 !important;
                min-height: unset !important;
                max-height: 92vh;
                overflow-y: auto;
                padding-bottom: env(safe-area-inset-bottom, 12px);
            }
            /* Centered / small dialogs stay centered */
            .modal-dialog.modal-dialog-centered {
                align-self: center !important;
                margin: 0 16px !important;
                max-width: calc(100% - 32px) !important;
                width: auto !important;
            }
            .modal-dialog.modal-dialog-centered .modal-content {
                border-radius: 20px !important;
                max-height: 90vh;
                overflow-y: auto;
            }
            .modal-dialog.modal-sm {
                align-self: center !important;
                margin: 0 auto !important;
                max-width: calc(100% - 40px) !important;
                width: auto !important;
            }
            .modal-dialog.modal-sm .modal-content {
                border-radius: 18px !important;
                min-height: unset !important;
            }

            /* ── Buttons bigger touch targets ── */
            .btn-sm { padding: 7px 14px !important; font-size: 13px !important; }
            .btn-xs { padding: 5px 10px !important; font-size: 12px !important; }

            /* ── Topbar title shorter ── */
            #topbar .topbar-title { font-size: 14px; }
            #topbar .uname { max-width: 72px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 12px; }

            /* ── Footer mobile ── */
            #footer { margin-left: 0; font-size: 11px; text-align: center; }
            #footer .float-end { float: none !important; display: block; }
        }

        /* ── GLOBAL PAGE LOADER ── */
        #page-loader {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(255,255,255,.82);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }
        #page-loader.active { display: flex; }
        .pl-icons { display: flex; align-items: center; gap: 14px; }
        .pl-icon {
            font-size: 36px;
            display: inline-block;
            animation: pl-bounce .7s ease-in-out infinite;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,.15));
        }
        .pl-icon:nth-child(1) { animation-delay: 0s; }
        .pl-icon:nth-child(2) { animation-delay: .14s; }
        .pl-icon:nth-child(3) { animation-delay: .28s; }
        @keyframes pl-bounce {
            0%, 100% { transform: translateY(0) scale(1); }
            45%       { transform: translateY(-14px) scale(1.12); }
        }

        /* ── MODALS Bootstrap 5 ── */
        .modal-header { border-bottom: 1px solid #edf2f7; }
        .modal-footer { border-top: 1px solid #edf2f7; }

        /* Bottom-sheet drag handle */
        @media (max-width: 767px) {
            .modal-content::before {
                content: '';
                display: block;
                width: 40px; height: 4px;
                background: #d1d5db;
                border-radius: 99px;
                margin: 10px auto 0;
                flex-shrink: 0;
            }
            .modal-dialog.modal-dialog-centered .modal-content::before,
            .modal-dialog.modal-sm .modal-content::before { display: none; }
        }

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

{{-- GLOBAL PAGE LOADER --}}
<div id="page-loader">
    <div class="pl-icons">
        <span class="pl-icon">👕</span>
        <span class="pl-icon">🩳</span>
        <span class="pl-icon">👟</span>
    </div>
</div>

{{-- SIDEBAR --}}
<div id="sidebar-overlay" onclick="closeSidebar()"></div>
<nav id="sidebar">
    <div class="sidebar-brand">
        <span>ORIGINAL 100%</span>
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
        <a class="topbar-title" href="javascript:location.reload()" style="text-decoration:none;color:inherit;">@yield('page_title')</a>
    @endif

    @yield('topbar_search')

    <div class="topbar-spacer"></div>

    <div class="dropdown">
        <div class="topbar-user dropdown-toggle" data-bs-toggle="dropdown">
            <img src="{{ asset('user-profile.png') }}" alt="User">
            <span class="uname">{{ Auth::user()->name }}</span>
        </div>
        <ul class="dropdown-menu dropdown-menu-end" style="min-width:180px; border-radius:10px; border:none; box-shadow:0 8px 24px rgba(0,0,0,0.12);">
            <!-- <li>
                <a class="dropdown-item" href="{{ route('user.change-password') }}">
                    <i class="fa fa-key me-2 text-muted"></i> პაროლის შეცვლა
                </a>
            </li> -->
            <!-- <li><hr class="dropdown-divider"></li> -->
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
    <strong>&copy; {{ date('Y') }} Original 100%</strong>
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
{{-- DataTables Responsive --}}
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>



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

// ── Global page loader ─────────────────────────────────────────────────
var _loaderTimer = null;
function showLoader() { /* disabled */ }
function hideLoader() { /* disabled */ }
$(document)
    .ajaxStart(showLoader)
    .ajaxStop(hideLoader);

// fallback: hide if no AJAX fires at all (static pages)
$(function() {
    setTimeout(hideLoader, 1500);
});

// absolute max: force-hide after 5s regardless of AJAX state
setTimeout(function() {
    var el = document.getElementById('page-loader');
    if (el) el.classList.remove('active');
}, 5000);

// non-AJAX form submit
$(document).on('submit', 'form:not([data-no-loader])', showLoader);

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

// ── DataTables global responsive default ──────────────────────────────
$.extend(true, $.fn.dataTable.defaults, {
    responsive: {
        details: {
            display: $.fn.dataTable.Responsive.display.childRow,
            renderer: $.fn.dataTable.Responsive.renderer.listHiddenNodes()
        }
    }
});

// ── PWA Service Worker ─────────────────────────────────────────────────
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function() {});
}
</script>

@if(session('role_restricted'))
<script>
$(function() {
    Swal.fire({
        icon: 'warning',
        title: 'შეზღუდულია',
        text: 'ეს მოქმედება შეზღუდულია. მიმართეთ ადმინისტრატორს.',
        confirmButtonText: 'კარგი',
    });
});
</script>
@endif

@yield('bot')

@auth
{{-- ── Announcement popup (ყველა logged-in user) ── --}}
<div id="ann-popup"
     style="display:none;position:fixed;bottom:24px;right:24px;z-index:1060;
            width:min(340px, calc(100vw - 32px));
            background:#fff;border-radius:16px;
            box-shadow:0 8px 32px rgba(0,0,0,.18);
            border:1.5px solid #e2e8f0;overflow:hidden;">
    <div style="background:#1e293b;padding:10px 16px;display:flex;align-items:center;gap:8px;">
        <i class="fa fa-bullhorn" style="color:#60a5fa;font-size:13px;"></i>
        <span id="ann-popup-title" style="color:#fff;font-size:12px;font-weight:700;flex:1;">შეტყობინება</span>
        <button id="btn-ann-dismiss"
                style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:14px;line-height:1;padding:0;">
            <i class="fa fa-xmark"></i>
        </button>
    </div>
    <div style="padding:14px 16px;">
        <p id="ann-popup-text"
           style="margin:0;font-size:14px;line-height:1.6;color:#1e293b;word-break:break-word;"></p>
    </div>
    <div style="padding:0 16px 14px;display:flex;justify-content:flex-end;">
        <button id="btn-ann-ok"
                class="btn btn-primary btn-sm px-3" style="font-size:13px;">
            გასაგებია 👍
        </button>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var _annPopup   = document.getElementById('ann-popup');
    var _annText    = document.getElementById('ann-popup-text');
    var _annTitle   = document.getElementById('ann-popup-title');
    var _annOk      = document.getElementById('btn-ann-ok');
    var _annDismiss = document.getElementById('btn-ann-dismiss');
    var _annId      = null;
    var _csrf       = '{{ csrf_token() }}';

    fetch('{{ route("announcements.latest") }}', { cache: 'no-store', credentials: 'same-origin' })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data.announcement) return;
            _annId = data.announcement.id;
            _annTitle.textContent = data.announcement.author || 'შეტყობინება';
            _annText.textContent  = data.announcement.message;
            _annPopup.style.display = 'block';
        });

    function markAnnRead() {
        if (!_annId) return;
        var id = _annId;
        _annId = null;
        if (_annOk)      _annOk.disabled = true;
        if (_annDismiss) _annDismiss.disabled = true;
        fetch('/announcements/' + id + '/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': _csrf },
            credentials: 'same-origin',
            keepalive: true
        }).finally(function() {
            _annPopup.style.display = 'none';
            if (_annOk)      _annOk.disabled = false;
            if (_annDismiss) _annDismiss.disabled = false;
        });
    }

    if (_annOk)      _annOk.addEventListener('click', markAnnRead);
    if (_annDismiss) _annDismiss.addEventListener('click', markAnnRead);
});
</script>
@endauth

@if(Auth::check() && Auth::user()->role === 'admin')
{{-- ── Announcements Modal (global, admin-only) ── --}}
<div class="modal fade" id="modal-announce" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:520px;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 8px 40px rgba(0,0,0,.18);">
            <div class="modal-header" style="border:none;padding:20px 24px 12px;">
                <h6 class="modal-title fw-bold" style="font-size:15px;">
                    <i class="fa fa-bullhorn me-2" style="color:#2563eb;"></i>შეტყობინებები
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:0 24px 8px;">
                {{-- New message form --}}
                <div style="background:#f8fafc;border-radius:12px;padding:16px;margin-bottom:16px;border:1px solid #e2e8f0;">
                    <p style="margin:0 0 8px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">ახალი შეტყობინება</p>
                    <textarea id="ann-message" class="form-control" rows="3"
                              maxlength="500" placeholder="შეტყობინების ტექსტი..."
                              style="resize:none;font-size:14px;border-color:#e2e8f0;"></textarea>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted" id="ann-char">0 / 500</small>
                        <button type="button" class="btn btn-primary btn-sm px-3" id="btn-ann-send">
                            <i class="fa fa-paper-plane me-1"></i>გაგზავნა
                        </button>
                    </div>
                </div>
                {{-- History list --}}
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">ისტორია</p>
                <div id="ann-list" style="display:flex;flex-direction:column;gap:8px;">
                    <span style="font-size:13px;color:#94a3b8;">იტვირთება...</span>
                </div>
            </div>
            <div class="modal-footer" style="border:none;padding:12px 24px 20px;">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">დახურვა</button>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var csrf = '{{ csrf_token() }}';

    function getModal() {
        return bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-announce'));
    }

    window.annOpenModal = function() {
        getModal().show();
        loadList();
    };

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function loadList() {
        var $el = $('#ann-list');
        if (!$el.length) return;
        $el.html('<span style="font-size:13px;color:#94a3b8;">იტვირთება...</span>');
        $.ajax({
            url: '/announcements',
            method: 'GET',
            cache: false,
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function(list) {
                $el.empty();
                if (!list.length) {
                    $el.html('<span style="font-size:13px;color:#94a3b8;">შეტყობინებები არ არის</span>');
                    return;
                }
                $.each(list, function(i, a) {
                    var $row = $('<div>').css({display:'flex','align-items':'flex-start',gap:'10px',background:'#f8fafc','border-radius':'10px',padding:'10px 14px',border:'1px solid #e9edf3'});
                    $row.html(
                        '<div style="flex:1;min-width:0;">' +
                            '<p style="margin:0 0 4px;font-size:13px;color:#1e293b;line-height:1.5;word-break:break-word;">' + escHtml(a.message) + '</p>' +
                            '<small style="color:#94a3b8;">' + escHtml(a.created_at) + ' · ' + escHtml(a.author || '') + ' · <b>' + a.reads + '</b> ნახა · ' +
                                '<span style="color:' + (a.is_active ? '#16a34a' : '#94a3b8') + ';">' + (a.is_active ? 'აქტიური' : 'გამორთული') + '</span>' +
                            '</small>' +
                        '</div>' +
                        '<div style="display:flex;flex-direction:column;gap:4px;flex-shrink:0;">' +
                            '<button onclick="annToggle(' + a.id + ',this)" class="btn btn-sm ' + (a.is_active ? 'btn-success' : 'btn-secondary') + '" style="font-size:11px;padding:2px 8px;">' +
                                (a.is_active ? 'ჩართ.' : 'გამო.') +
                            '</button>' +
                            '<button onclick="annDelete(' + a.id + ',this)" class="btn btn-sm btn-outline-danger" style="font-size:11px;padding:2px 8px;"><i class="fa fa-trash"></i></button>' +
                        '</div>'
                    );
                    $el.append($row);
                });
            },
            error: function() {
                $el.html('<span style="font-size:13px;color:#ef4444;">სია ვერ ჩაიტვირთა</span>');
            }
        });
    }

    $('#ann-message').on('input', function() {
        $('#ann-char').text(this.value.length + ' / 500');
    });

    $('#btn-ann-send').on('click', function() {
        var msg = $('#ann-message').val().trim();
        if (!msg) { $('#ann-message').focus(); return; }
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            url: '/announcements',
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
            contentType: 'application/json',
            data: JSON.stringify({ message: msg }),
            success: function() {
                $('#ann-message').val('');
                $('#ann-char').text('0 / 500');
                loadList();
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    window.annToggle = function(id, btn) {
        $.ajax({
            url: '/announcements/' + id + '/toggle',
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function() { loadList(); }
        });
    };

    window.annDelete = function(id, btn) {
        var $btn = $(btn);
        if ($btn.data('confirm') !== '1') {
            $btn.data('confirm', '1').text('დარწმუნებული?')
                .removeClass('btn-outline-danger').addClass('btn-danger');
            setTimeout(function() {
                if ($btn.data('confirm') === '1') {
                    $btn.data('confirm', '').html('<i class="fa fa-trash"></i>')
                        .removeClass('btn-danger').addClass('btn-outline-danger');
                }
            }, 3000);
            return;
        }
        $btn.prop('disabled', true);
        $.ajax({
            url: '/announcements/' + id,
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
            success: function() { loadList(); },
            error: function() { $btn.prop('disabled', false); }
        });
    };
});
</script>
@endif

</body>
</html>