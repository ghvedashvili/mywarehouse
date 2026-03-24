<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; background: #f4f6f8; padding: 20px; }

        .page { max-width: 800px; margin: auto; background: #fff; border-radius: 12px; overflow: hidden; }

        /* ── ჰედერი ── */
        .top-header {
            padding: 20px 40px;
            display: table;
            width: 100%;
            border-bottom: 2px solid #e85d26;
        }
        .top-header-left { display: table-cell; vertical-align: middle; }
        .top-header-left h2 { font-size: 20px; color: #1a1a1a; }
        .top-header-left p { font-size: 12px; color: #888; margin-top: 2px; }
        .top-header-right { display: table-cell; text-align: right; vertical-align: middle; }
        .top-header-right span {
            font-size: 22px;
            font-weight: 700;
            color: #e85d26;
            letter-spacing: 2px;
        }

        /* ── ორდერი ── */
        .order-item {
            display: table;
            width: 100%;
            padding: 20px 40px;
            border-bottom: 1px solid #eee;
        }
        .order-image { display: table-cell; width: 90px; vertical-align: top; }
        .order-image img {
            width: 80px; height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }
        .no-image {
            width: 80px; height: 80px;
            border-radius: 8px;
            border: 1px solid #eee;
            background: #f4f6f8;
        }
        .order-info { display: table-cell; vertical-align: top; padding-left: 16px; }
        .order-info h3 { font-size: 14px; margin-bottom: 6px; }
        .order-info p { font-size: 12px; color: #555; margin: 2px 0; }
        .order-meta { display: table-cell; text-align: right; vertical-align: top; width: 160px; }
        .order-meta .order-id { font-size: 12px; color: #aaa; margin-bottom: 4px; }
        .order-meta .order-customer { font-size: 13px; font-weight: 600; color: #1a1a1a; margin-bottom: 4px; }
        .order-meta .order-price { font-size: 15px; font-weight: 700; color: #e85d26; }
        .order-meta .order-status { font-size: 11px; color: #888; margin-top: 4px; }

        /* ── ფუტერი ── */
        .bottom-footer {
            padding: 16px 40px;
            background: #fafafa;
            display: table;
            width: 100%;
            font-size: 12px;
            color: #777;
            border-top: 1px solid #eee;
        }
        .bottom-footer-left { display: table-cell; }
        .bottom-footer-right { display: table-cell; text-align: right; font-weight: 600; color: #1a1a1a; }
    </style>
</head>
<body>
<div class="page">

    <!-- ჰედერი — ერთხელ -->
    <div class="top-header">
        <div class="top-header-left">
            <h2>Orders Export</h2>
            <p>{{ now()->format('d M Y') }} &nbsp;·&nbsp; სულ: {{ $product_Order->count() }} პროდუქტი</p>
        </div>
        <div class="top-header-right">
            <span>Originals 100%</span>
        </div>
    </div>

    <!-- ორდერები -->
    @foreach($product_Order as $product_order)
    <div class="order-item">
<!-- {{ $product_order }} -->
        <div class="order-image">
            @if($product_order->imageBase64)
                <img src="{{ $product_order->imageBase64 }}" alt="Product">
            @else
                <div class="no-image"></div>
            @endif
        </div>

        <div class="order-info">
            <h3>{{ $product_order->product->name }}</h3>
            @if($product_order->product_size)
                <p><strong>ზომა:</strong> {{ $product_order->product_size }}</p>
            @endif
            @if($product_order->comment)
                <p><strong>კომენტარი:</strong> {{ $product_order->comment }}</p>
            @endif
            @if($product_order->discount > 0)
                <p style="color:#888;">ფასდაკლება: -{{ $product_order->discount }} ₾</p>
            @endif

             <p><strong>ღირებულება:</strong> {{ $product_order->price_georgia }} ლარი</p>
        </div>

        <div class="order-meta">
            <div class="order-id">#{{ $product_order->id }}</div>
            <div class="order-customer">{{ $product_order->customer->name }}</div>
            <div class="order-customer" style="font-weight:400; font-size:11px;">{{ $product_order->customer->tel }}</div>
           <div class="order-customer">{{ $product_order->customer->address }}</div>
             <div class="order-customer" style="font-weight:400; font-size:11px;">{{ $product_order->customer->city->name ?? '' }}</div>  {{-- ეს დაამატე --}}
           @if($product_order->orderStatus)
    <div class="order-status">{{ $product_order->orderStatus->name }}</div>
            @endif
        </div>

    </div>
    @endforeach

    <!-- ფუტერი — ერთხელ -->
    <div class="bottom-footer">
        <div class="bottom-footer-left">Thank you for your purchase</div>
        <div class="bottom-footer-right">Orginals 100%</div>
    </div>

</div>
</body>
</html>