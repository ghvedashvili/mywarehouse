<!DOCTYPE html>
<html lang="ka">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif !important; margin:0; padding:0; box-sizing:border-box; }
        body { background:#fff; padding:16px 20px; font-size:11px; color:#1a1a1a; }

        .doc-header { display:table; width:100%; border-bottom:2px solid #e85d26; padding-bottom:10px; margin-bottom:14px; }
        .doc-header-left  { display:table-cell; vertical-align:middle; }
        .doc-header-left h2 { font-size:15px; }
        .doc-header-left p  { font-size:10px; color:#999; margin-top:2px; }
        .doc-header-right   { display:table-cell; text-align:right; vertical-align:middle; width:80px; }
        .doc-header-right img { max-height:40px; width:auto; }

        .cut-line { display:table; width:100%; margin:10px 0; }
        .cut-icon { display:table-cell; vertical-align:middle; width:18px; font-size:13px; color:#bbb; }
        .cut-dash { display:table-cell; vertical-align:middle; border-top:1.5px dashed #ccc; }

        .prod-row { display:table; width:100%; padding:6px 0; border-bottom:1px solid #f0f0f0; }
        .prod-row:last-child { border-bottom:none; }
        .pr-img  { display:table-cell; vertical-align:middle; width:96px; }
        .pr-img img  { width:88px; height:88px; object-fit:cover; border-radius:5px; border:1px solid #e0e0e0; }
        .pr-img .no-img { width:88px; height:88px; border-radius:5px; border:1px solid #e0e0e0; background:#f4f6f8; display:block; }
        .pr-info { display:table-cell; vertical-align:middle; padding-left:10px; }
        .pr-name { font-size:12px; font-weight:700; }
        .pr-meta { font-size:10px; color:#666; margin-top:3px; }
        .pr-action { display:table-cell; vertical-align:middle; text-align:right; width:90px; }
        .badge-deliver { background:#d1fae5; color:#065f46; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }
        .badge-pickup  { background:#fee2e2; color:#991b1b; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }
        .badge-paid    { background:#d1fae5; color:#065f46; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }
        .badge-unpaid  { background:#fee2e2; color:#991b1b; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }

        .order-row { display:table; width:100%; padding:4px 0; }
        .col-num { display:table-cell; vertical-align:middle; width:18px; color:#bbb; font-size:10px; }
        .col-order-num { display:table-cell; vertical-align:middle; text-align:right; width:90px; color:#888; font-size:10px; font-weight:700; }

        .cust-header { display:table; width:100%; padding:8px 12px; }
        .cust-left  { display:table-cell; vertical-align:middle; }
        .cust-right { display:table-cell; vertical-align:middle; text-align:right; width:130px; }
        .cust-name  { font-size:12px; font-weight:700; }
        .cust-meta  { font-size:10px; color:#666; margin-top:3px; line-height:1.5; }
        .cust-num   { font-size:10px; color:#888; margin-top:2px; }

        .group-block { border:1.5px solid #e85d26; border-radius:6px; overflow:hidden; }
        .group-block .cust-header { background:#fff5f0; border-bottom:1px solid #f0c0a8; }
        .group-items { padding:8px 12px; }

        .exchange-block { position:relative; border:2px solid #f59e0b; border-radius:6px; overflow:hidden; background:#fffbeb; }
        .exchange-block .cust-header { background:#fef3c7; border-bottom:1px solid #fcd34d; }
        .exchange-watermark {
            position:absolute; top:28px; left:0; right:0;
            text-align:center; font-size:52px; font-weight:900;
            color:#fde68a; letter-spacing:4px;
        }
        .exchange-content { position:relative; padding:8px 12px; }

        .return-block { position:relative; border:2px solid #ef4444; border-radius:6px; overflow:hidden; background:#fff5f5; }
        .return-block .cust-header { background:#fee2e2; border-bottom:1px solid #fca5a5; }
        .return-watermark {
            position:absolute; top:28px; left:0; right:0;
            text-align:center; font-size:52px; font-weight:900;
            color:#fecaca; letter-spacing:4px;
        }
        .return-content { position:relative; padding:8px 12px; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>

@php
    $totalCount = $singles->count() + $twos->count() + $threes->count() + $manys->count() + $specials->count();
    $hasPrev = false;
@endphp

<div class="doc-header">
    <div class="doc-header-left">
        <h2>კურიერისთვის — დღეს გაგზავნილი</h2>
        <p>{{ now()->format('d.m.Y') }} &nbsp;·&nbsp; {{ $totalCount }} ორდერი</p>
    </div>
    <div class="doc-header-right">
        @if(isset($logoBase64) && $logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo">
        @endif
    </div>
</div>

{{-- ══ SINGLES (1 product orders) ══ --}}
@if($singles->isNotEmpty())
    @foreach($singles as $i => $group)
        @if($i > 0)
            <div class="cut-line">
                <div class="cut-icon">&#9986;</div>
                <div class="cut-dash"></div>
            </div>
        @endif
        @include('product_order._group_block', ['group' => $group, 'orderIndex' => $i])
    @endforeach
    @php $hasPrev = true; @endphp
@endif

{{-- ══ TWOS (2-children groups, 3 per page) ══ --}}
@if($twos->isNotEmpty())
    @foreach($twos as $i => $group)
        @if($i === 0 && $hasPrev)
            <div class="page-break"></div>
        @elseif($i > 0 && $i % 3 === 0)
            <div class="page-break"></div>
        @elseif($i > 0)
            <div class="cut-line">
                <div class="cut-icon">&#9986;</div>
                <div class="cut-dash"></div>
            </div>
        @endif
        @include('product_order._group_block', ['group' => $group])
    @endforeach
    @php $hasPrev = true; @endphp
@endif

{{-- ══ THREES (3-children groups, 2 per page) ══ --}}
@if($threes->isNotEmpty())
    @foreach($threes as $i => $group)
        @if($i === 0 && $hasPrev)
            <div class="page-break"></div>
        @elseif($i > 0 && $i % 2 === 0)
            <div class="page-break"></div>
        @elseif($i > 0)
            <div class="cut-line">
                <div class="cut-icon">&#9986;</div>
                <div class="cut-dash"></div>
            </div>
        @endif
        @include('product_order._group_block', ['group' => $group])
    @endforeach
    @php $hasPrev = true; @endphp
@endif

{{-- ══ MANYS (>3 children, each on own page) ══ --}}
@if($manys->isNotEmpty())
    @foreach($manys as $i => $group)
        @if($i > 0 || $hasPrev)
            <div class="page-break"></div>
        @endif
        @include('product_order._group_block', ['group' => $group])
    @endforeach
    @php $hasPrev = true; @endphp
@endif

{{-- ══ SPECIALS (exchange + return, each on own page) ══ --}}
@if($specials->isNotEmpty())
    @foreach($specials as $i => $group)
        @if($i > 0 || $hasPrev)
            <div class="page-break"></div>
        @endif
        @include('product_order._group_block', ['group' => $group])
    @endforeach
@endif

</body>
</html>
