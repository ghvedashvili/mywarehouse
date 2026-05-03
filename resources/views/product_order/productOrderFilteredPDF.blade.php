<!DOCTYPE html>
<html lang="ka">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif !important;
            margin: 0; padding: 0; box-sizing: border-box;
        }
        body {
            background: #fff;
            padding: 16px 20px;
            font-size: 11px;
            color: #1a1a1a;
        }

        /* ── document header ── */
        .doc-header {
            display: table;
            width: 100%;
            border-bottom: 2px solid #e85d26;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .doc-header-left  { display: table-cell; vertical-align: middle; }
        .doc-header-left h2 { font-size: 15px; }
        .doc-header-left p  { font-size: 10px; color: #999; margin-top: 2px; }
        .doc-header-right {
            display: table-cell; text-align: right;
            vertical-align: middle; width: 80px;
        }
        .doc-header-right img { max-height: 40px; width: auto; }

        /* ── cut separator ── */
        .cut-line {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .cut-icon {
            display: table-cell;
            vertical-align: middle;
            width: 18px;
            font-size: 12px;
            color: #bbb;
        }
        .cut-dash {
            display: table-cell;
            vertical-align: middle;
            border-top: 1.5px dashed #ccc;
        }

        /* ── single order row ── */
        .order-row {
            display: table;
            width: 100%;
            padding: 4px 0;
        }
        .col-num {
            display: table-cell; vertical-align: middle;
            width: 18px; color: #bbb; font-size: 10px;
        }
        .col-img {
            display: table-cell; vertical-align: middle; width: 96px;
        }
        .col-img img {
            width: 88px; height: 88px; object-fit: cover;
            border-radius: 5px; border: 1px solid #e0e0e0;
        }
        .col-img .no-img {
            width: 88px; height: 88px; border-radius: 5px;
            border: 1px solid #e0e0e0; background: #f4f6f8;
            display: block;
        }
        .col-product {
            display: table-cell; vertical-align: middle;
            width: 34%; padding-left: 10px;
        }
        .col-product .p-name { font-size: 12px; font-weight: 700; }
        .col-product .p-meta { font-size: 10px; color: #666; margin-top: 3px; }
        .col-customer { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .col-customer .c-name { font-size: 12px; font-weight: 700; }
        .col-customer .c-meta { font-size: 10px; color: #666; margin-top: 3px; line-height: 1.5; }
        .col-order-num {
            display: table-cell; vertical-align: middle;
            text-align: right; width: 60px;
            color: #888; font-size: 10px; font-weight: 700;
        }

        /* ── grouped order block ── */
        .group-block {
            border: 1.5px solid #e85d26;
            border-radius: 6px;
            overflow: hidden;
        }
        .group-header {
            display: table;
            width: 100%;
            background: #fff5f0;
            padding: 8px 12px;
            border-bottom: 1px solid #f0c0a8;
        }
        .group-header-left  { display: table-cell; vertical-align: middle; }
        .group-header-right {
            display: table-cell; vertical-align: middle;
            text-align: right; width: 120px;
        }
        .group-customer-name  { font-size: 12px; font-weight: 700; }
        .group-customer-meta  { font-size: 10px; color: #666; margin-top: 3px; line-height: 1.5; }
        .group-total   { font-size: 14px; font-weight: 700; color: #e85d26; }
        .group-order-num { font-size: 10px; color: #888; margin-top: 2px; }

        .group-items { padding: 8px 12px; }

        .group-item {
            display: table;
            width: 100%;
            padding: 6px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .group-item:last-child { border-bottom: none; }
        .gi-img {
            display: table-cell; vertical-align: middle; width: 96px;
        }
        .gi-img img {
            width: 88px; height: 88px; object-fit: cover;
            border-radius: 5px; border: 1px solid #e0e0e0;
        }
        .gi-img .no-img {
            width: 88px; height: 88px; border-radius: 5px;
            border: 1px solid #e0e0e0; background: #f4f6f8; display: block;
        }
        .gi-info { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .gi-name { font-size: 12px; font-weight: 700; }
        .gi-meta { font-size: 10px; color: #666; margin-top: 3px; }
        .gi-price {
            display: table-cell; vertical-align: middle;
            text-align: right; width: 70px;
            font-size: 11px; font-weight: 700; color: #1a1a1a;
        }
    </style>
</head>
<body>

    <div class="doc-header">
        <div class="doc-header-left">
            <h2>ორდერების სია</h2>
            <p>{{ now()->format('d.m.Y') }} &nbsp;·&nbsp; {{ $groups->count() }} ორდერი</p>
        </div>
        <div class="doc-header-right">
            @if(isset($logoBase64) && $logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @endif
        </div>
    </div>

    @foreach($groups as $index => $group)
        @php
            $primary  = $group['primary'];
            $children = $group['children'];
            $isGroup  = $group['is_group'];
            $orderNum = $primary->order_number ?? ('#' . $primary->id);
        @endphp

        @if($index > 0)
            <div class="cut-line">
                <div class="cut-icon">&#9986;</div>
                <div class="cut-dash"></div>
            </div>
        @endif

        @if($isGroup)
            {{-- ── GROUPED ORDER ── --}}
            @php
                $allMembers = collect([$primary])->merge($children);
                $total = $allMembers->sum(fn($o) => (float)$o->price_georgia - (float)($o->discount ?? 0));
                $customer = $primary->customer;
            @endphp
            <div class="group-block">

                <div class="group-header">
                    <div class="group-header-left">
                        <div class="group-customer-name">{{ $customer->name ?? '—' }}</div>
                        <div class="group-customer-meta">
                            {{ $customer->tel ?? '' }}
                            @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                            <br>
                            {{ $customer->city->name ?? '' }}
                            @if($customer->address ?? ''), {{ $customer->address }}@endif
                        </div>
                    </div>
                    <div class="group-header-right">
                        <div class="group-total">{{ number_format($total, 2) }} ₾</div>
                        <div class="group-order-num">{{ $orderNum }}</div>
                    </div>
                </div>

                <div class="group-items">
                    @foreach($allMembers as $member)
                        <div class="group-item">
                            <div class="gi-img">
                                @if(!empty($member->imageBase64))
                                    <img src="{{ $member->imageBase64 }}" alt="">
                                @else
                                    <div class="no-img"></div>
                                @endif
                            </div>
                            <div class="gi-info">
                                <div class="gi-name">{{ $member->product->name ?? '—' }}</div>
                                <div class="gi-meta">
                                    @if($member->product_size)ზომა: {{ $member->product_size }}@endif
                                    @if($member->comment) &nbsp;·&nbsp; {{ $member->comment }}@endif
                                </div>
                            </div>
                            <div class="gi-price">
                                {{ number_format((float)$member->price_georgia - (float)($member->discount ?? 0), 2) }} ₾
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>

        @else
            {{-- ── SINGLE ORDER ── --}}
            <div class="order-row">
                <div class="col-num">{{ $index + 1 }}</div>

                <div class="col-img">
                    @if(!empty($primary->imageBase64))
                        <img src="{{ $primary->imageBase64 }}" alt="">
                    @else
                        <div class="no-img"></div>
                    @endif
                </div>

                <div class="col-product">
                    <div class="p-name">{{ $primary->product->name ?? '—' }}</div>
                    <div class="p-meta">
                        @if($primary->product_size)ზომა: {{ $primary->product_size }}<br>@endif
                        ღირებულება: {{ number_format((float)$primary->price_georgia - (float)($primary->discount ?? 0), 2) }} ₾
                        @if($primary->comment)<br>{{ $primary->comment }}@endif
                    </div>
                </div>

                <div class="col-customer">
                    <div class="c-name">{{ $primary->customer->name ?? '—' }}</div>
                    <div class="c-meta">
                        {{ $primary->customer->tel ?? '' }}
                        @if($primary->customer->alternative_tel ?? '') / {{ $primary->customer->alternative_tel }}@endif
                        <br>
                        {{ $primary->customer->city->name ?? '' }}
                        @if($primary->customer->address ?? ''), {{ $primary->customer->address }}@endif
                    </div>
                </div>

                <div class="col-order-num">{{ $orderNum }}</div>
            </div>
        @endif

    @endforeach

</body>
</html>
