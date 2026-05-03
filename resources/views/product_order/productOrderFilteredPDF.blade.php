<!DOCTYPE html>
<html lang="ka">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="utf-8">
    <style>
        * { font-family: 'DejaVu Sans', sans-serif !important; margin:0; padding:0; box-sizing:border-box; }
        body { background:#fff; padding:16px 20px; font-size:11px; color:#1a1a1a; }

        /* ── document header ── */
        .doc-header { display:table; width:100%; border-bottom:2px solid #e85d26; padding-bottom:10px; margin-bottom:14px; }
        .doc-header-left  { display:table-cell; vertical-align:middle; }
        .doc-header-left h2 { font-size:15px; }
        .doc-header-left p  { font-size:10px; color:#999; margin-top:2px; }
        .doc-header-right   { display:table-cell; text-align:right; vertical-align:middle; width:80px; }
        .doc-header-right img { max-height:40px; width:auto; }

        /* ── cut separator ── */
        .cut-line { display:table; width:100%; margin:10px 0; }
        .cut-icon { display:table-cell; vertical-align:middle; width:18px; font-size:13px; color:#bbb; }
        .cut-dash { display:table-cell; vertical-align:middle; border-top:1.5px dashed #ccc; }

        /* ── shared product row (inside any block) ── */
        .prod-row { display:table; width:100%; padding:6px 0; border-bottom:1px solid #f0f0f0; }
        .prod-row:last-child { border-bottom:none; }
        .pr-img  { display:table-cell; vertical-align:middle; width:96px; }
        .pr-img img  { width:88px; height:88px; object-fit:cover; border-radius:5px; border:1px solid #e0e0e0; }
        .pr-img .no-img { width:88px; height:88px; border-radius:5px; border:1px solid #e0e0e0; background:#f4f6f8; display:block; }
        .pr-info { display:table-cell; vertical-align:middle; padding-left:10px; }
        .pr-name { font-size:12px; font-weight:700; }
        .pr-meta { font-size:10px; color:#666; margin-top:3px; }
        .pr-price { display:table-cell; vertical-align:middle; text-align:right; width:70px; font-size:11px; font-weight:700; }
        .pr-action { display:table-cell; vertical-align:middle; text-align:right; width:90px; }
        .badge-deliver { background:#d1fae5; color:#065f46; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }
        .badge-pickup  { background:#fee2e2; color:#991b1b; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }

        /* ── normal single order ── */
        .order-row { display:table; width:100%; padding:4px 0; }
        .col-num { display:table-cell; vertical-align:middle; width:18px; color:#bbb; font-size:10px; }
        .col-order-num { display:table-cell; vertical-align:middle; text-align:right; width:60px; color:#888; font-size:10px; font-weight:700; }

        /* ── customer header row (reused) ── */
        .cust-header { display:table; width:100%; padding:8px 12px; }
        .cust-left  { display:table-cell; vertical-align:middle; }
        .cust-right { display:table-cell; vertical-align:middle; text-align:right; width:130px; }
        .cust-name  { font-size:12px; font-weight:700; }
        .cust-meta  { font-size:10px; color:#666; margin-top:3px; line-height:1.5; }
        .cust-total { font-size:14px; font-weight:700; }
        .cust-num   { font-size:10px; color:#888; margin-top:2px; }

        /* ── grouped order block ── */
        .group-block { border:1.5px solid #e85d26; border-radius:6px; overflow:hidden; }
        .group-block .cust-header { background:#fff5f0; border-bottom:1px solid #f0c0a8; }
        .group-block .cust-total  { color:#e85d26; }
        .group-items { padding:8px 12px; }

        /* ── exchange block ── */
        .exchange-block { position:relative; border:2px solid #f59e0b; border-radius:6px; overflow:hidden; background:#fffbeb; }
        .exchange-block .cust-header { background:#fef3c7; border-bottom:1px solid #fcd34d; }
        .exchange-block .cust-total  { color:#b45309; }
        .exchange-watermark {
            position:absolute; top:28px; left:0; right:0;
            text-align:center; font-size:52px; font-weight:900;
            color:#fde68a; letter-spacing:4px;
        }
        .exchange-content { position:relative; padding:8px 12px; }

        /* ── return block ── */
        .return-block { position:relative; border:2px solid #ef4444; border-radius:6px; overflow:hidden; background:#fff5f5; }
        .return-block .cust-header { background:#fee2e2; border-bottom:1px solid #fca5a5; }
        .return-block .cust-total  { color:#b91c1c; }
        .return-watermark {
            position:absolute; top:28px; left:0; right:0;
            text-align:center; font-size:52px; font-weight:900;
            color:#fecaca; letter-spacing:4px;
        }
        .return-content { position:relative; padding:8px 12px; }
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
        $isExch   = $group['is_exchange'];
        $origOrd  = $group['original_order'];
        $isReturn = $group['is_return'];
        $orderNum = $primary->order_number ?? ('#' . $primary->id);
        $customer = $primary->customer;
        $totalNew = (float)$primary->price_georgia - (float)($primary->discount ?? 0);
        $totalOld = $origOrd ? ((float)$origOrd->price_georgia - (float)($origOrd->discount ?? 0)) : 0;
    @endphp

    @if($index > 0)
        <div class="cut-line">
            <div class="cut-icon">&#9986;</div>
            <div class="cut-dash"></div>
        </div>
    @endif

    {{-- ══ EXCHANGE ORDER ══ --}}
    @if($isExch)
        <div class="exchange-block">
            <div class="exchange-watermark">გადაცვლა</div>

            <div class="cust-header">
                <div class="cust-left">
                    <div class="cust-name">{{ $customer->name ?? '—' }}</div>
                    <div class="cust-meta">
                        {{ $customer->tel ?? '' }}
                        @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                        <br>
                        {{ $customer->city->name ?? '' }}
                        @if($customer->address ?? ''), {{ $customer->address }}@endif
                    </div>
                </div>
                <div class="cust-right">
                    <div class="cust-total">{{ number_format($totalNew, 2) }} GEL</div>
                    <div class="cust-num">{{ $orderNum }}</div>
                </div>
            </div>

            <div class="exchange-content">
                {{-- new product: deliver --}}
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($primary->imageBase64))
                            <img src="{{ $primary->imageBase64 }}" alt="">
                        @else
                            <div class="no-img"></div>
                        @endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $primary->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                            @if($primary->comment) · {{ $primary->comment }}@endif
                        </div>
                    </div>
                    <div class="pr-price">{{ number_format($totalNew, 2) }} GEL</div>
                    <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
                </div>

                {{-- original product: pick up --}}
                @if($origOrd)
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($origOrd->imageBase64))
                            <img src="{{ $origOrd->imageBase64 }}" alt="">
                        @else
                            <div class="no-img"></div>
                        @endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $origOrd->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($origOrd->product_size)ზომა: {{ $origOrd->product_size }}@endif
                        </div>
                    </div>
                    <div class="pr-price" style="color:#991b1b;">{{ number_format($totalOld, 2) }} GEL</div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
                @endif
            </div>
        </div>

    {{-- ══ RETURN ORDER ══ --}}
    @elseif($isReturn)
        <div class="return-block">
            <div class="return-watermark">დაბრუნება</div>

            <div class="cust-header">
                <div class="cust-left">
                    <div class="cust-name">{{ $customer->name ?? '—' }}</div>
                    <div class="cust-meta">
                        {{ $customer->tel ?? '' }}
                        @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                        <br>
                        {{ $customer->city->name ?? '' }}
                        @if($customer->address ?? ''), {{ $customer->address }}@endif
                    </div>
                </div>
                <div class="cust-right">
                    <div class="cust-total">{{ number_format($totalNew, 2) }} GEL</div>
                    <div class="cust-num">{{ $orderNum }}</div>
                </div>
            </div>

            <div class="return-content">
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($primary->imageBase64))
                            <img src="{{ $primary->imageBase64 }}" alt="">
                        @else
                            <div class="no-img"></div>
                        @endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $primary->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                            @if($primary->comment) · {{ $primary->comment }}@endif
                        </div>
                    </div>
                    <div class="pr-price" style="color:#991b1b;">{{ number_format($totalNew, 2) }} GEL</div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
            </div>
        </div>

    {{-- ══ GROUPED ORDER ══ --}}
    @elseif($isGroup)
        @php
            $allMembers = collect([$primary])->merge($children);
            $groupTotal = $allMembers->sum(fn($o) => (float)$o->price_georgia - (float)($o->discount ?? 0));
        @endphp
        <div class="group-block">
            <div class="cust-header">
                <div class="cust-left">
                    <div class="cust-name">{{ $customer->name ?? '—' }}</div>
                    <div class="cust-meta">
                        {{ $customer->tel ?? '' }}
                        @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                        <br>
                        {{ $customer->city->name ?? '' }}
                        @if($customer->address ?? ''), {{ $customer->address }}@endif
                    </div>
                </div>
                <div class="cust-right">
                    <div class="cust-total">{{ number_format($groupTotal, 2) }} GEL</div>
                    <div class="cust-num">{{ $orderNum }}</div>
                </div>
            </div>
            <div class="group-items">
                @foreach($allMembers as $member)
                    <div class="prod-row">
                        <div class="pr-img">
                            @if(!empty($member->imageBase64))
                                <img src="{{ $member->imageBase64 }}" alt="">
                            @else
                                <div class="no-img"></div>
                            @endif
                        </div>
                        <div class="pr-info">
                            <div class="pr-name">{{ $member->product->name ?? '—' }}</div>
                            <div class="pr-meta">
                                @if($member->product_size)ზომა: {{ $member->product_size }}@endif
                                @if($member->comment) · {{ $member->comment }}@endif
                            </div>
                        </div>
                        <div class="pr-price">{{ number_format((float)$member->price_georgia - (float)($member->discount ?? 0), 2) }} GEL</div>
                    </div>
                @endforeach
            </div>
        </div>

    {{-- ══ NORMAL SINGLE ORDER ══ --}}
    @else
        <div class="order-row">
            <div class="col-num">{{ $index + 1 }}</div>
            <div class="pr-img">
                @if(!empty($primary->imageBase64))
                    <img src="{{ $primary->imageBase64 }}" alt="">
                @else
                    <div class="no-img"></div>
                @endif
            </div>
            <div class="pr-info">
                <div class="pr-name">{{ $primary->product->name ?? '—' }}</div>
                <div class="pr-meta">
                    @if($primary->product_size)ზომა: {{ $primary->product_size }}<br>@endif
                    ღირებულება: {{ number_format($totalNew, 2) }} GEL
                    @if($primary->comment)<br>{{ $primary->comment }}@endif
                </div>
            </div>
            <div class="pr-info" style="width:38%;">
                <div class="cust-name">{{ $customer->name ?? '—' }}</div>
                <div class="cust-meta">
                    {{ $customer->tel ?? '' }}
                    @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                    <br>
                    {{ $customer->city->name ?? '' }}
                    @if($customer->address ?? ''), {{ $customer->address }}@endif
                </div>
            </div>
            <div class="col-order-num">{{ $orderNum }}</div>
        </div>
    @endif

@endforeach

</body>
</html>
