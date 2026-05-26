<!DOCTYPE html>
<html lang="ka">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif !important; margin:0; padding:0; box-sizing:border-box; }
        body { background:#fff; padding:16px 20px; font-size:11px; color:#1a1a1a; }

        .doc-header { display:table; width:100%; border-bottom:2px solid #f59e0b; padding-bottom:10px; margin-bottom:14px; }
        .doc-header-left  { display:table-cell; vertical-align:middle; }
        .doc-header-left h2 { font-size:15px; }
        .doc-header-left p  { font-size:10px; color:#999; margin-top:2px; }
        .doc-header-right   { display:table-cell; text-align:right; vertical-align:middle; width:80px; }
        .doc-header-right img { max-height:40px; width:auto; }

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

        .cust-header { display:table; width:100%; padding:8px 12px; }
        .cust-left  { display:table-cell; vertical-align:middle; }
        .cust-right { display:table-cell; vertical-align:middle; text-align:right; width:130px; }
        .cust-name  { font-size:12px; font-weight:700; }
        .cust-meta  { font-size:10px; color:#666; margin-top:3px; line-height:1.5; }
        .cust-num   { font-size:10px; color:#888; margin-top:2px; }

        .exchange-block { position:relative; border:2px solid #f59e0b; border-radius:6px; overflow:hidden; background:#fffbeb; }
        .exchange-block .cust-header { background:#fef3c7; border-bottom:1px solid #fcd34d; }
        .exchange-watermark {
            position:absolute; top:28px; left:0; right:0;
            text-align:center; font-size:52px; font-weight:900;
            color:#fde68a; letter-spacing:4px;
        }
        .exchange-content { position:relative; padding:8px 12px; }
    </style>
</head>
<body>

@php
    $customer = $changeOrder->customer;
    $orderNum = $changeOrder->order_number ?? ('#' . $changeOrder->id);
    $isPaid   = ((float)($changeOrder->courier_price_tbilisi ?? 0) > 0)
             || ((float)($changeOrder->courier_price_region   ?? 0) > 0)
             || ((float)($changeOrder->courier_price_village  ?? 0) > 0);
@endphp

<div class="doc-header">
    <div class="doc-header-left">
        <h2>გაცვლის ორდერი</h2>
        <p>{{ now()->format('d.m.Y') }} &nbsp;·&nbsp; {{ $orderNum }}</p>
    </div>
    <div class="doc-header-right">
        @if(isset($logoBase64) && $logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo">
        @endif
    </div>
</div>

<div class="exchange-block">
    <div class="exchange-watermark">გადაცვლა</div>

    <div class="cust-header">
        <div class="cust-left">
            <div class="cust-name">{{ $customer->name ?? '—' }}</div>
            <div class="cust-meta">
                {{ $customer->tel ?? '' }}
                @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                <br>{{ $customer->city->name ?? '' }}
                @if($customer->address ?? ''), {{ $customer->address }}@endif
            </div>
        </div>
        <div class="cust-right">
            <div>@if($isPaid)<span class="badge-paid">საკურიერო გადახდილია</span>@else<span class="badge-unpaid">საკურიერო გადასახდელია</span>@endif</div>
            <div class="cust-num">{{ $orderNum }}</div>
        </div>
    </div>

    <div class="exchange-content">
        {{-- ახალი პროდუქტი: გადაეცი --}}
        <div class="prod-row">
            <div class="pr-img">
                @if(!empty($changeOrder->imageBase64))
                    <img src="{{ $changeOrder->imageBase64 }}" alt="">
                @else
                    <div class="no-img"></div>
                @endif
            </div>
            <div class="pr-info">
                <div class="pr-name">{{ $changeOrder->product->name ?? '—' }}</div>
                <div class="pr-meta">
                    @if($changeOrder->product?->product_code)<span style="color:#888;font-size:10px;">{{ $changeOrder->product->product_code }}</span><br>@endif
                    @if($changeOrder->product_size)ზომა: {{ $changeOrder->product_size }}@endif
                    @if($changeOrder->comment)<br>{{ $changeOrder->comment }}@endif
                </div>
            </div>
            <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
        </div>

        {{-- ძველი პროდუქტი: წამოიღე --}}
        <div class="prod-row">
            <div class="pr-img">
                @if(!empty($originalSale->imageBase64))
                    <img src="{{ $originalSale->imageBase64 }}" alt="">
                @else
                    <div class="no-img"></div>
                @endif
            </div>
            <div class="pr-info">
                <div class="pr-name">{{ $originalSale->product->name ?? '—' }}</div>
                <div class="pr-meta">
                    @if($originalSale->product?->product_code)<span style="color:#888;font-size:10px;">{{ $originalSale->product->product_code }}</span><br>@endif
                    @if($originalSale->product_size)ზომა: {{ $originalSale->product_size }}@endif
                </div>
            </div>
            <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
        </div>
    </div>
</div>

</body>
</html>
