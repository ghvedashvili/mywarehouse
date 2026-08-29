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
        .badge-paid    { background:#d1fae5; color:#065f46; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }
        .badge-unpaid  { background:#fee2e2; color:#991b1b; font-size:10px; font-weight:700; padding:3px 7px; border-radius:4px; }

        /* ── normal single order ── */
        .order-row { display:table; width:100%; padding:4px 0; }
        .col-num { display:table-cell; vertical-align:middle; width:18px; color:#bbb; font-size:10px; }
        .col-order-num { display:table-cell; vertical-align:middle; text-align:right; width:90px; color:#888; font-size:10px; font-weight:700; }

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

        /* ── page break helpers ── */
        .order-wrap   { page-break-inside: avoid; }
        .section-break { page-break-before: always; }
        .page-only    { page-break-before: always; page-break-after: always; }
    </style>
</head>
<body>

@php
    $singles     = $groups->filter(fn($g) => !$g['is_exchange'] && !$g['is_return'] && $g['children']->isEmpty())->values();
    $multiGroups = $groups
        ->filter(fn($g) => !$g['is_exchange'] && !$g['is_return'] && $g['children']->isNotEmpty())
        ->groupBy(fn($g) => $g['children']->count() + 1)
        ->sortKeys();
    $returns     = $groups->filter(fn($g) => $g['is_return'])->values();
    $exchanges   = $groups->filter(fn($g) => $g['is_exchange'])->values();
    $totalCount  = $groups->count();
    // პირველი არაცარიელი სექცია page-break-ის გარეშე
    $firstSectionPrinted = false;
@endphp

<div class="doc-header">
    <div class="doc-header-left">
        <h2>{{ $title ?? 'ორდერების სია' }}</h2>
        <p>{{ now()->format('d.m.Y') }} &nbsp;·&nbsp; {{ $totalCount }} ორდერი</p>
    </div>
    <div class="doc-header-right">
        @if(isset($logoBase64) && $logoBase64)
            <img src="{{ $logoBase64 }}" alt="Logo">
        @endif
    </div>
</div>

{{-- ══ SECTION 1: SINGLE-PRODUCT ORDERS ══ --}}
@foreach($singles as $i => $group)
    @php
        $primary  = $group['primary'];
        $orderNum = $primary->order_number ?? ('#' . $primary->id);
        $customer = $primary->customer;
        $isPaid = ((float)($primary->courier_price_tbilisi ?? 0) > 0)
               || ((float)($primary->courier_price_region   ?? 0) > 0)
               || ((float)($primary->courier_price_village  ?? 0) > 0);
        $firstSectionPrinted = true;
    @endphp
    <div class="order-wrap">
        @if($i > 0)
            <div class="cut-line">
                <div class="cut-icon">&#9986;</div>
                <div class="cut-dash"></div>
            </div>
        @endif
        <div class="order-row">
            <div class="col-num">{{ $i + 1 }}</div>
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
                    @if($primary->product?->product_code)<span style="color:#888;font-size:10px;">{{ $primary->product->product_code }}</span><br>@endif
                    @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                    @if($primary->comment)<br><span style="color:#e74c3c;">{{ $primary->comment }}</span>@endif
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
            <div class="col-order-num">
                @if($isPaid)<span class="badge-paid">საკურიერო გადახდილია</span>@else<span class="badge-unpaid">საკურიერო გადასახდელია</span>@endif
                <div style="margin-top:3px;">{{ $orderNum }}</div>
            </div>
        </div>
    </div>
@endforeach

{{-- ══ SECTIONS: MULTI-PRODUCT ORDERS (grouped by count) ══ --}}
@foreach($multiGroups as $productCount => $items)
    @foreach($items as $i => $group)
        @php
            $primary  = $group['primary'];
            $children = $group['children'];
            $orderNum = $primary->order_number ?? ('#' . $primary->id);
            $customer = $primary->customer;
            $isPaid = ((float)($primary->courier_price_tbilisi ?? 0) > 0)
                   || ((float)($primary->courier_price_region   ?? 0) > 0)
                   || ((float)($primary->courier_price_village  ?? 0) > 0);
            $allMembers = collect([$primary])->merge($children);
        @endphp
        @php $needBreak = $i === 0 && $firstSectionPrinted; if ($i === 0) $firstSectionPrinted = true; @endphp
        <div class="order-wrap {{ $needBreak ? 'section-break' : '' }}">
            @if($i > 0)
                <div class="cut-line">
                    <div class="cut-icon">&#9986;</div>
                    <div class="cut-dash"></div>
                </div>
            @endif
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
                        <div>@if($isPaid)<span class="badge-paid">საკურიერო გადახდილია</span>@else<span class="badge-unpaid">საკურიერო გადასახდელია</span>@endif</div>
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
                                    @if($member->product?->product_code)<span style="color:#888;font-size:10px;">{{ $member->product->product_code }}</span><br>@endif
                                    @if($member->product_size)ზომა: {{ $member->product_size }}@endif
                                    @if($member->comment) · <span style="color:#e74c3c;">{{ $member->comment }}</span>@endif
                                </div>
                            </div>
                            @if($member->order_type === 'change')
                                <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
                            @endif
                        </div>
                        @if($member->order_type === 'change' && !empty($member->originalOrderData))
                        <div class="prod-row">
                            <div class="pr-img">
                                @if(!empty($member->originalOrderData->imageBase64))
                                    <img src="{{ $member->originalOrderData->imageBase64 }}" alt="">
                                @else
                                    <div class="no-img"></div>
                                @endif
                            </div>
                            <div class="pr-info">
                                <div class="pr-name">{{ $member->originalOrderData->product->name ?? '—' }}</div>
                                <div class="pr-meta">
                                    @if($member->originalOrderData->product?->product_code)<span style="color:#888;font-size:10px;">{{ $member->originalOrderData->product->product_code }}</span><br>@endif
                                    @if($member->originalOrderData->product_size)ზომა: {{ $member->originalOrderData->product_size }}@endif
                                </div>
                            </div>
                            <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endforeach

{{-- ══ RETURNS: each on its own page ══ --}}
@foreach($returns as $group)
    @php
        $primary  = $group['primary'];
        $orderNum = $primary->order_number ?? ('#' . $primary->id);
        $customer = $primary->customer;
        $isPaid = ((float)($primary->courier_price_tbilisi ?? 0) > 0)
               || ((float)($primary->courier_price_region   ?? 0) > 0)
               || ((float)($primary->courier_price_village  ?? 0) > 0);
        $breakClass = $firstSectionPrinted ? 'section-break' : '';
        $firstSectionPrinted = true;
    @endphp
    <div class="{{ $breakClass }}">
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
                    <div>@if($isPaid)<span class="badge-paid">საკურიერო გადახდილია</span>@else<span class="badge-unpaid">საკურიერო გადასახდელია</span>@endif</div>
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
                            @if($primary->product?->product_code)<span style="color:#888;font-size:10px;">{{ $primary->product->product_code }}</span><br>@endif
                            @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                            @if($primary->comment) · <span style="color:#e74c3c;">{{ $primary->comment }}</span>@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
            </div>
        </div>
    </div>
@endforeach

{{-- ══ EXCHANGES: each on its own page ══ --}}
@foreach($exchanges as $group)
    @php
        $primary  = $group['primary'];
        $origOrd  = $group['original_order'];
        $orderNum = $primary->order_number ?? ('#' . $primary->id);
        $customer = $primary->customer;
        $isPaid = ((float)($primary->courier_price_tbilisi ?? 0) > 0)
               || ((float)($primary->courier_price_region   ?? 0) > 0)
               || ((float)($primary->courier_price_village  ?? 0) > 0);
        $breakClass = $firstSectionPrinted ? 'section-break' : '';
        $firstSectionPrinted = true;
    @endphp
    <div class="{{ $breakClass }}">
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
                    <div>@if($isPaid)<span class="badge-paid">საკურიერო გადახდილია</span>@else<span class="badge-unpaid">საკურიერო გადასახდელია</span>@endif</div>
                    <div class="cust-num">{{ $orderNum }}</div>
                </div>
            </div>
            <div class="exchange-content">
                {{-- change order: გადაეცი (new) + წამოიღე (original) --}}
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
                            @if($primary->product?->product_code)<span style="color:#888;font-size:10px;">{{ $primary->product->product_code }}</span><br>@endif
                            @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                            @if($primary->comment)<br><span style="color:#e74c3c;">{{ $primary->comment }}</span>@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
                </div>
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
                            @if($origOrd->product?->product_code)<span style="color:#888;font-size:10px;">{{ $origOrd->product->product_code }}</span><br>@endif
                            @if($origOrd->product_size)ზომა: {{ $origOrd->product_size }}@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
                @endif
                {{-- merged children --}}
                @foreach($group['children'] ?? [] as $child)
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($child->imageBase64))
                            <img src="{{ $child->imageBase64 }}" alt="">
                        @else
                            <div class="no-img"></div>
                        @endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $child->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($child->product?->product_code)<span style="color:#888;font-size:10px;">{{ $child->product->product_code }}</span><br>@endif
                            @if($child->product_size)ზომა: {{ $child->product_size }}@endif
                            @if($child->comment)<br><span style="color:#e74c3c;">{{ $child->comment }}</span>@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
                </div>
                @if($child->order_type === 'change' && !empty($child->originalOrderData))
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($child->originalOrderData->imageBase64))
                            <img src="{{ $child->originalOrderData->imageBase64 }}" alt="">
                        @else
                            <div class="no-img"></div>
                        @endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $child->originalOrderData->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($child->originalOrderData->product?->product_code)<span style="color:#888;font-size:10px;">{{ $child->originalOrderData->product->product_code }}</span><br>@endif
                            @if($child->originalOrderData->product_size)ზომა: {{ $child->originalOrderData->product_size }}@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>
@endforeach

</body>
</html>
