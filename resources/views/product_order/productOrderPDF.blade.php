<!DOCTYPE html>
<html lang="ka">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ინვოისი #{{ $product_order->id }}</title>
    <style>
        /* ქართული სიმბოლოებისთვის აუცილებელია DejaVu Sans */
        body { 
            font-family: 'DejaVu Sans', sans-serif; 
            background: #f4f6f8; 
            margin: 0; 
            padding: 20px; 
            font-size: 12px;
        }

        .page {
            max-width: 760px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }

        /* ── Header ── */
        .header {
            padding: 40px 48px;
            border-bottom: 1px solid #ebebeb;
            overflow: hidden;
        }

        .logo-area { float: left; }
        .invoice-meta { float: right; text-align: right; }

        .invoice-meta .label {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }

        /* ── Sections ── */
        .section {
            padding: 30px 48px;
            border-bottom: 1px solid #ebebeb;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 10px;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 15px;
            letter-spacing: 1px;
        }

        /* ── Grid ── */
        .details-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .details-table td {
            width: 50%;
            vertical-align: top;
            padding-bottom: 15px;
        }

        .detail-label { font-size: 10px; color: #aaa; text-transform: uppercase; }
        .detail-value { font-size: 13px; color: #1a1a1a; font-weight: bold; }

        /* ── Product Table ── */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .product-table th {
            text-align: left;
            font-size: 11px;
            color: #999;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 10px;
        }

        .product-table td {
            padding: 15px 0;
            border-bottom: 1px solid #ebebeb;
            vertical-align: middle;
        }

        .product-img {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            margin-right: 10px;
        }

        /* ── Footer ── */
        .footer {
            padding: 20px 48px;
            background: #fafaf8;
            overflow: hidden;
        }

        .thank-you { float: left; color: #888; }
        .brand { float: right; font-weight: bold; color: #1a1a1a; }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>
<body>
<div class="page">

    <div class="header clearfix">
        <div class="logo-area">
            <img src="https://via.placeholder.com/120x50/e85d26/ffffff?text=LOGO" alt="Logo">
        </div>
        <div class="invoice-meta">
            <div class="label">ინვოისი</div>
            <p>ნომერი: <span>#{{ $product_order->id }}</span></p>
            <p>თარიღი: <span>{{ $product_order->tanggal }}</span></p>
        </div>
    </div>

    <div class="section">
        <div class="section-title">მყიდველი</div>
        <div class="company-name">{{ $product_order->customer->name }}</div>
        <p>ტელ: {{ $product_order->customer->tel }}</p>
        @if($product_order->customer->email)
            <p>ელ-ფოსტა: {{ $product_order->customer->email }}</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">შეკვეთის დეტალები</div>
        <table class="details-table">
            <tr>
                <td>
                    <div class="detail-label">პროდუქტი</div>
                    <div class="detail-value">{{ $product_order->product->name }}</div>
                </td>
                <td>
                    <div class="detail-label">რაოდენობა</div>
                    <div class="detail-value">{{ $product_order->tel }} ცალი</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="section" style="border-bottom: none;">
        <table class="product-table">
            <thead>
                <tr>
                    <th>დასახელება</th>
                    <th style="text-align: right;">რაოდენობა</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @if(isset($imageBase64) && $imageBase64)
                            <img src="{{ $imageBase64 }}" class="product-img" style="float: left;">
                        @endif
                        <div style="padding-top: 10px;">{{ $product_order->product->name }}</div>
                    </td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ $product_order->tel }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="footer clearfix">
        <span class="thank-you">გმადლობთ შენაძენისთვის!</span>
        <span class="brand">Original 100%</span>
    </div>

</div>
</body>
</html>