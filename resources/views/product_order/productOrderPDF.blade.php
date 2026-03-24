<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $product_order->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Serif+Display&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f0;
            color: #1a1a1a;
            padding: 40px 20px;
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
            padding: 40px 48px 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid #ebebeb;
        }

        .logo-area img {
            height: 52px;
            width: auto;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-meta .label {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #1a1a1a;
            margin-bottom: 8px;
        }

        .invoice-meta p {
            font-size: 13px;
            color: #666;
            line-height: 1.7;
        }

        .invoice-meta span {
            color: #1a1a1a;
            font-weight: 500;
        }

        /* ── Company info ── */
        .company-section {
            padding: 32px 48px;
            border-bottom: 1px solid #ebebeb;
        }

        .company-name {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 10px;
            color: #1a1a1a;
        }

        .company-section p {
            font-size: 13px;
            color: #555;
            line-height: 1.8;
        }

        /* ── Details grid ── */
        .details-section {
            padding: 32px 48px;
            border-bottom: 1px solid #ebebeb;
        }

        .section-title {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 20px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px 40px;
        }

        .detail-item .detail-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            color: #aaa;
            margin-bottom: 4px;
        }

        .detail-item .detail-value {
            font-size: 14px;
            font-weight: 500;
            color: #1a1a1a;
        }

        /* ── Product table ── */
        .table-section {
            padding: 0 48px 32px;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table thead tr {
            border-bottom: 2px solid #1a1a1a;
        }

        .product-table thead th {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #999;
            padding: 0 0 12px;
            text-align: left;
        }

        .product-table thead th:last-child {
            text-align: right;
        }

        .product-table tbody tr {
            border-bottom: 1px solid #ebebeb;
        }

        .product-table tbody td {
            padding: 16px 0;
            font-size: 14px;
            color: #1a1a1a;
            vertical-align: middle;
        }

        .product-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .product-img {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #ebebeb;
            vertical-align: middle;
            margin-right: 12px;
        }

        .product-name {
            font-weight: 500;
        }

        /* ── Footer ── */
        .footer {
            padding: 28px 48px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafaf8;
            border-top: 1px solid #ebebeb;
        }

        .footer .thank-you {
            font-size: 13px;
            color: #888;
        }

        .footer .brand {
            font-size: 13px;
            font-weight: 600;
            color: #1a1a1a;
            letter-spacing: 0.5px;
        }

        /* ── Print ── */
        @media print {
            body { background: white; padding: 0; }
            .page { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Header -->
    <div class="header">
        <div class="logo-area">
            {{-- Replace with your logo --}}
            <img src="https://via.placeholder.com/120x52/e85d26/ffffff?text=LOGO" alt="Logo">
        </div>
        <div class="invoice-meta">
            <div class="label">Invoice</div>
            <p>Invoice Number: <span>#{{ $product_order->id }}</span></p>
            <p>Date: <span>{{ $product_order->tanggal }}</span></p>
        </div>
    </div>

    <!-- Company / Billed To -->
    <div class="company-section">
        <div class="company-name">{{ $product_order->customer->name }}</div>
        <p>{{ $product_order->customer->tel }}</p>
        <p>{{ $product_order->customer->alternative_tel }}</p>
        <p>{{ $product_order->customer->email }}</p>
    </div>

    <!-- Order Details -->
    <div class="details-section">
        <div class="section-title">Order Details</div>
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-label">Product</div>
                <div class="detail-value">{{ $product_order->product->name }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Quantity</div>
                <div class="detail-value">{{ $product_order->tel }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Customer</div>
                <div class="detail-value">{{ $product_order->customer->name }}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Invoice Date</div>
                <div class="detail-value">{{ $product_order->tanggal }}</div>
            </div>
        </div>
    </div>

    <!-- Product Table -->
    <div class="table-section">
        <table class="product-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qty</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        @if($imageBase64)
                            <img src="{{ $imageBase64 }}" class="product-img" alt="Product">
                        @endif
                        <span class="product-name">{{ $product_order->product->name }}</span>
                    </td>
                    <td>{{ $product_order->tel }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <span class="thank-you">Thank you for your order.</span>
        <span class="brand">I M S</span>
    </div>

</div>
</body>
</html>