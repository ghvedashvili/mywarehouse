@php
    $primary  = $group['primary'];
    $children = $group['children'];
    $isGroup  = $group['is_group'];
    $isExch   = $group['is_exchange'];
    $origOrd  = $group['original_order'];
    $isReturn = $group['is_return'];
    $orderNum = $primary->order_number ?? ('#' . $primary->id);
    $customer = $primary->customer;
    $isPaid   = ((float)($primary->courier_price_tbilisi ?? 0) > 0)
             || ((float)($primary->courier_price_region   ?? 0) > 0)
             || ((float)($primary->courier_price_village  ?? 0) > 0);
    $allMembers = collect([$primary])->merge($children);
@endphp

@if($isExch)
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
                <div>@if($isPaid)<span class="badge-paid">საკურიეროს გადახდილია</span>@else<span class="badge-unpaid">საკურიეროს გადასახდელია</span>@endif</div>
                <div class="cust-num">{{ $orderNum }}</div>
            </div>
        </div>
        <div class="exchange-content">
            @php $exchangeMembers = collect([$primary])->merge($children); @endphp
            @foreach($exchangeMembers as $member)
                @if(!$loop->first)
                    <div style="border-top:1px dashed #fcd34d;margin:4px 0;"></div>
                @endif
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($member->imageBase64))<img src="{{ $member->imageBase64 }}" alt="">
                        @else<div class="no-img"></div>@endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $member->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($member->product?->product_code)<span style="color:#888;font-size:10px;">{{ $member->product->product_code }}</span><br>@endif
                            @if($member->product_size)ზომა: {{ $member->product_size }}@endif
                            @if($member->comment)<br><span style="color:rgb(220,38,38);font-weight:600;">{{ $member->comment }}</span>@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
                </div>
                @php $memberOrig = $member->originalOrderData ?? null; @endphp
                @if($memberOrig)
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($memberOrig->imageBase64))<img src="{{ $memberOrig->imageBase64 }}" alt="">
                        @else<div class="no-img"></div>@endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $memberOrig->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($memberOrig->product?->product_code)<span style="color:#888;font-size:10px;">{{ $memberOrig->product->product_code }}</span><br>@endif
                            @if($memberOrig->product_size)ზომა: {{ $memberOrig->product_size }}@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

@elseif($isReturn)
    <div class="return-block">
        <div class="return-watermark">დაბრუნება</div>
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
                <div>@if($isPaid)<span class="badge-paid">საკურიეროს გადახდილია</span>@else<span class="badge-unpaid">საკურიეროს გადასახდელია</span>@endif</div>
                <div class="cust-num">{{ $orderNum }}</div>
            </div>
        </div>
        <div class="return-content">
            <div class="prod-row">
                <div class="pr-img">
                    @if(!empty($primary->imageBase64))<img src="{{ $primary->imageBase64 }}" alt="">
                    @else<div class="no-img"></div>@endif
                </div>
                <div class="pr-info">
                    <div class="pr-name">{{ $primary->product->name ?? '—' }}</div>
                    <div class="pr-meta">
                        @if($primary->product?->product_code)<span style="color:#888;font-size:10px;">{{ $primary->product->product_code }}</span><br>@endif
                        @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                        @if($primary->comment) · <span style="color:rgb(220,38,38);font-weight:600;">{{ $primary->comment }}</span>@endif
                    </div>
                </div>
                <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
            </div>
        </div>
    </div>

@elseif($isGroup)
    <div class="group-block">
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
                <div>@if($isPaid)<span class="badge-paid">საკურიეროს გადახდილია</span>@else<span class="badge-unpaid">საკურიეროს გადასახდელია</span>@endif</div>
                <div class="cust-num">{{ $orderNum }}</div>
            </div>
        </div>
        <div class="group-items">
            @foreach($allMembers as $member)
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($member->imageBase64))<img src="{{ $member->imageBase64 }}" alt="">
                        @else<div class="no-img"></div>@endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $member->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($member->product?->product_code)<span style="color:#888;font-size:10px;">{{ $member->product->product_code }}</span><br>@endif
                            @if($member->product_size)ზომა: {{ $member->product_size }}@endif
                            @if($member->comment) · <span style="color:rgb(220,38,38);font-weight:600;">{{ $member->comment }}</span>@endif
                        </div>
                    </div>
                    @if($member->order_type === 'change')
                        <div class="pr-action"><span class="badge-deliver">&#10003; გადაეცი</span></div>
                    @endif
                </div>
                @php $memberOrig = $member->originalOrderData ?? null; @endphp
                @if($memberOrig)
                <div class="prod-row">
                    <div class="pr-img">
                        @if(!empty($memberOrig->imageBase64))<img src="{{ $memberOrig->imageBase64 }}" alt="">
                        @else<div class="no-img"></div>@endif
                    </div>
                    <div class="pr-info">
                        <div class="pr-name">{{ $memberOrig->product->name ?? '—' }}</div>
                        <div class="pr-meta">
                            @if($memberOrig->product?->product_code)<span style="color:#888;font-size:10px;">{{ $memberOrig->product->product_code }}</span><br>@endif
                            @if($memberOrig->product_size)ზომა: {{ $memberOrig->product_size }}@endif
                        </div>
                    </div>
                    <div class="pr-action"><span class="badge-pickup">&#8617; წამოიღე</span></div>
                </div>
                @endif
            @endforeach
        </div>
    </div>

@else
    <div class="order-row">
        <div class="col-num" style="display:table-cell;vertical-align:middle;width:18px;color:#bbb;font-size:10px;">
            {{ isset($orderIndex) ? $orderIndex + 1 : '' }}
        </div>
        <div class="pr-img">
            @if(!empty($primary->imageBase64))<img src="{{ $primary->imageBase64 }}" alt="">
            @else<div class="no-img"></div>@endif
        </div>
        <div class="pr-info">
            <div class="pr-name">{{ $primary->product->name ?? '—' }}</div>
            <div class="pr-meta">
                @if($primary->product?->product_code)<span style="color:#888;font-size:10px;">{{ $primary->product->product_code }}</span><br>@endif
                @if($primary->product_size)ზომა: {{ $primary->product_size }}@endif
                @if($primary->comment)<br><span style="color:rgb(220,38,38);font-weight:600;">{{ $primary->comment }}</span>@endif
            </div>
        </div>
        <div class="pr-info" style="width:38%;">
            <div class="cust-name">{{ $customer->name ?? '—' }}</div>
            <div class="cust-meta">
                {{ $customer->tel ?? '' }}
                @if($customer->alternative_tel ?? '') / {{ $customer->alternative_tel }}@endif
                <br>{{ $customer->city->name ?? '' }}
                @if($customer->address ?? ''), {{ $customer->address }}@endif
            </div>
        </div>
        <div class="col-order-num">
            @if($isPaid)<span class="badge-paid">საკურიეროს გადახდილია</span>@else<span class="badge-unpaid">საკურიეროს გადასახდელია</span>@endif
            <div style="margin-top:3px;">{{ $orderNum }}</div>
        </div>
    </div>
@endif
