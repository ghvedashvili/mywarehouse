<!DOCTYPE html>
<html lang="ka">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif !important;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif !important;
            background: #f4f6f8;
            padding: 20px;
        }

        .page {
            max-width: 800px;
            margin: auto;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
        }

        /* ════ HEADER ════ */
        .top-header {
            padding: 20px 20px;
            display: table;
            width: 100%;
            border-bottom: 2px solid #8e44ad;
        }

        .top-header-left {
            display: table-cell;
            vertical-align: middle;
        }

        .top-header-left h2 {
            font-size: 20px;
            color: #1a1a1a;
        }

        .top-header-left p {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        .top-header-right {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
            width: 150px;
        }

        .top-header-right img {
            max-height: 100px;
            width: auto;
            display: block;
            margin-left: auto;
        }

        /* ════ EXCHANGE BANNER ════ */
        .exchange-banner {
            background: #f5eef8;
            border-left: 4px solid #8e44ad;
            padding: 10px 20px;
            font-size: 12px;
            color: #6c3483;
        }

        .exchange-banner strong {
            font-size: 13px;
        }

        /* ════ SECTION LABEL ════ */
        .section-label {
            padding: 8px 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #fff;
        }

        .section-label.old {
            background: #95a5a6;
        }

        .section-label.new {
            background: #8e44ad;
        }

        /* ════ ORDER ITEM ════ */
        .order-item {
            display: table;
            width: 100%;
            padding: 20px 20px;
            border-bottom: 1px solid #eee;
        }

        .order-item.dimmed {
            opacity: 0.6;
            background: #fafafa;
        }

        .order-image {
            display: table-cell;
            width: 120px;
            vertical-align: top;
        }

        .order-image img {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .no-image {
            width: 110px;
            height: 110px;
            border-radius: 8px;
            border: 1px solid #eee;
            background: #f4f6f8;
            display: block;
        }

        .order-info {
            display: table-cell;
            vertical-align: top;
            padding-left: 16px;
        }

        .order-info h3 {
            font-size: 14px;
            margin-bottom: 6px;
        }

        .order-info p {
            font-size: 12px;
            color: #555;
            margin: 2px 0;
        }

        .order-meta {
            display: table-cell;
            text-align: right;
            vertical-align: top;
            width: 200px;
        }

        .order-meta .order-id {
            font-size: 12px;
            color: #aaa;
            margin-bottom: 4px;
        }

        .order-meta .order-customer {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 4px;
        }

        /* ════ ARROW DIVIDER ════ */
        .arrow-divider {
            text-align: center;
            padding: 8px 0;
            font-size: 20px;
            color: #8e44ad;
            background: #fff;
            border-bottom: 1px solid #eee;
        }

        /* ════ FOOTER ════ */
        .bottom-footer {
            padding: 16px 20px;
            background: #fafafa;
            display: table;
            width: 100%;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
        }

        .bottom-footer-left  { display: table-cell; }
        .bottom-footer-right {
            display: table-cell;
            text-align: right;
            font-weight: 600;
            color: #1a1a1a;
        }
    </style>
</head>
<body>

<div class="page">

    {{-- ════ HEADER ════ --}}
    <div class="top-header">
        <div class="top-header-left">
            <h2>გაცვლის ორდერი</h2>
            <p>{{ now()->format('d M Y') }} &nbsp;·&nbsp; C#{{ $changeOrder->id }}</p>
        </div>
        <div class="top-header-right">
            @if(isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
    </div>

    {{-- ════ BANNER ════ --}}
    <div class="exchange-banner">
        <strong>🔄 გაცვლა</strong>
        &nbsp;·&nbsp;
        S#{{ $originalSale->id }} გაიცვალა C#{{ $changeOrder->id }}-ით
        &nbsp;·&nbsp;
        {{ $changeOrder->customer->name ?? '' }}
    </div>

    {{-- ════ ძველი პროდუქტი ════ --}}
    <div class="section-label old">↩ გაცვლილი პროდუქტი (ძველი)</div>

    <div class="order-item dimmed">
        <div class="order-image">
            @if($originalSale->imageBase64)
                <img src="{{ $originalSale->imageBase64 }}" alt="Old Product">
            @else
                <div class="no-image"></div>
            @endif
        </div>

        <div class="order-info">
            <h3>{{ $originalSale->product->name ?? '—' }}</h3>
            @if($originalSale->product_size)
                <p><strong>ზომა:</strong> {{ $originalSale->product_size }}</p>
            @endif
            @if($originalSale->discount > 0)
                <p style="color:#888;">ფასდაკლება: -{{ $originalSale->discount }} ₾</p>
            @endif
            <p><strong>ღირებულება:</strong> {{ $originalSale->price_georgia }} ₾</p>
            @if($originalSale->comment)
                <p><strong>კომენტარი:</strong> {{ $originalSale->comment }}</p>
            @endif
        </div>

        <div class="order-meta">
            <div class="order-id">S#{{ $originalSale->id }}</div>
            <div class="order-customer" style="color:#95a5a6;">გაცვლილია</div>
        </div>
    </div>

    {{-- ════ ARROW ════ --}}
    <div class="arrow-divider">↓</div>

    {{-- ════ ახალი პროდუქტი ════ --}}
    <div class="section-label new">🔄 ახალი პროდუქტი</div>

    <div class="order-item">
        <div class="order-image">
            @if($changeOrder->imageBase64)
                <img src="{{ $changeOrder->imageBase64 }}" alt="New Product">
            @else
                <div class="no-image"></div>
            @endif
        </div>

        <div class="order-info">
            <h3>{{ $changeOrder->product->name ?? '—' }}</h3>
            @if($changeOrder->product_size)
                <p><strong>ზომა:</strong> {{ $changeOrder->product_size }}</p>
            @endif
            @if($changeOrder->discount > 0)
                <p style="color:#888;">ფასდაკლება: -{{ $changeOrder->discount }} ₾</p>
            @endif
            <p><strong>ღირებულება:</strong> {{ $changeOrder->price_georgia }} ₾</p>

            {{-- ფასთა სხვაობა --}}
            @php
                $diff = $changeOrder->price_georgia - $originalSale->price_georgia;
            @endphp
            @if(abs($diff) > 0.01)
                <p style="color:{{ $diff > 0 ? '#e74c3c' : '#27ae60' }}; font-weight:600;">
                    ფასთა სხვაობა: {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }} ₾
                </p>
            @endif

            @if($changeOrder->comment)
                <p><strong>კომენტარი:</strong> {{ $changeOrder->comment }}</p>
            @endif
        </div>

        <div class="order-meta">
            <div class="order-customer">{{ $changeOrder->customer->name ?? '—' }}</div>
            <div class="order-customer" style="font-weight:400; font-size:11px;">
                {{ $changeOrder->customer->tel ?? '' }}
                @if($changeOrder->customer->alternative_tel ?? false)
                    / {{ $changeOrder->customer->alternative_tel }}
                @endif
            </div>
            <div class="order-customer" style="font-weight:400; font-size:11px;">
                {{ $changeOrder->customer->city->name ?? '' }},
                {{ $changeOrder->customer->address ?? '' }}
            </div>
            <div class="order-id">C#{{ $changeOrder->id }}</div>
        </div>
    </div>

    {{-- ════ FOOTER ════ --}}
    <div class="bottom-footer">
        <div class="bottom-footer-left">გმადლობთ შენაძენისთვის</div>
        <div class="bottom-footer-right">Original 100% - ის გუნდი34321</div>
    </div>

</div>

</body>
</html>