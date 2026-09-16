<!DOCTYPE html>
<html lang="ka">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif !important; margin:0; padding:0; box-sizing:border-box; }
        body { background:#fff; padding:16px 20px; font-size:11px; color:#1a1a1a; }

        .doc-header { display:table; width:100%; border-bottom:2px solid #16a34a; padding-bottom:10px; margin-bottom:14px; }
        .doc-header-left  { display:table-cell; vertical-align:middle; }
        .doc-header-left h2 { font-size:16px; }
        .doc-header-left p  { font-size:10px; color:#999; margin-top:2px; }
        .doc-header-right   { display:table-cell; text-align:right; vertical-align:middle; width:80px; }
        .doc-header-right img { max-height:40px; width:auto; }

        .cat-title {
            font-size:12px; font-weight:700; color:#fff; background:#16a34a;
            padding:5px 10px; margin:16px 0 6px 0; border-radius:3px;
        }
        .cat-title:first-of-type { margin-top:0; }

        .prod-table { width:100%; border-collapse:collapse; margin-bottom:2px; }
        .prod-row { display:table; width:100%; border-bottom:1px solid #eee; padding:5px 0; }
        .prod-row:nth-child(even) { background:#fafafa; }

        .col-num  { display:table-cell; width:20px; vertical-align:middle; color:#999; font-size:9px; }
        .col-img  { display:table-cell; width:46px; vertical-align:middle; }
        .col-img img { width:40px; height:40px; object-fit:cover; border-radius:3px; border:1px solid #e0e0e0; }
        .col-img .no-img { width:40px; height:40px; border-radius:3px; border:1px solid #e0e0e0; background:#f4f6f8; display:block; }

        .col-name { display:table-cell; width:26%; vertical-align:middle; padding-left:8px; }
        .col-name .p-name { font-size:11px; font-weight:700; }
        .col-name .p-code { font-size:9px; color:#888; margin-top:2px; }

        .col-sizes { display:table-cell; vertical-align:middle; padding-left:8px; }
        .size-table { border-collapse:collapse; }
        .size-table td {
            border:1px solid #ddd; text-align:center; padding:2px 6px;
            font-size:9px; min-width:24px;
        }
        .size-table .size-head { background:#f0fdf4; color:#166534; font-weight:700; }
        .size-table .size-qty  { font-weight:700; }
        .size-table .size-qty.zero { color:#bbb; font-weight:400; }

        .no-sizes { color:#bbb; font-size:9px; }

        .footer-note { margin-top:16px; font-size:9px; color:#999; text-align:right; }
    </style>
</head>
<body>

<div class="doc-header">
    <div class="doc-header-left">
        <h2>საწყობის ნაშთი</h2>
        <p>{{ now()->format('d.m.Y H:i') }}</p>
    </div>
    <div class="doc-header-right">
        @if($logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo">
        @endif
    </div>
</div>

@foreach($grouped as $categoryName => $products)
    <div class="cat-title">{{ $categoryName }}</div>
    <div class="prod-table">
        @foreach($products as $i => $product)
            <div class="prod-row">
                <div class="col-num">{{ $i + 1 }}</div>
                <div class="col-img">
                    @if($product->imageBase64)
                        <img src="{{ $product->imageBase64 }}" alt="">
                    @else
                        <div class="no-img"></div>
                    @endif
                </div>
                <div class="col-name">
                    <div class="p-name">{{ $product->name }}</div>
                    @if($product->product_code)<div class="p-code">{{ $product->product_code }}</div>@endif
                </div>
                <div class="col-sizes">
                    @if($product->stockRows->isEmpty())
                        <span class="no-sizes">— ნაშთი არ დაფიქსირებულა —</span>
                    @else
                        <table class="size-table">
                            <tr>
                                @foreach($product->stockRows as $row)
                                    <td class="size-head">{{ $row->size ?: '—' }}</td>
                                @endforeach
                            </tr>
                            <tr>
                                @foreach($product->stockRows as $row)
                                    <td class="size-qty {{ $row->physical_qty <= 0 ? 'zero' : '' }}">{{ $row->physical_qty }}</td>
                                @endforeach
                            </tr>
                        </table>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endforeach

<div class="footer-note">სულ {{ $grouped->flatten(1)->count() }} პროდუქტი</div>

</body>
</html>
