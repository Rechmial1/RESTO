<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if($order->invoice_type == 'FA')
    <title>{{ restaurant()->name }} - {{ $order->order_number ?? "" }}</title>
    @else
    <title>{{ restaurant()->name }} - {{ $order->show_formatted_order_number ?? "" }}</title>
    @endif
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
        }

        [dir="rtl"] {
            text-align: right;
        }

        [dir="ltr"] {
            text-align: left;
        }

        .receipt {
            width: {{ $width - 5 }}mm;
            padding: {{ $thermal ? '1mm' : '6.35mm' }};
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .restaurant-logo {
            width: 80px;
            height: 80px;
            margin-top: 3px;
            object-fit: contain;
        }

        .restaurant-name {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        .restaurant-name img {
            display: block;
            margin: 0 auto 2mm;
        }

        .qr-code-img {
            width: 100px;
            height: 100px;
        }

        .restaurant-info {
            font-size: 9pt;
            margin-bottom: 1mm;
        }

        .order-info {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 2mm 0;
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        /* Style spécifique pour les avoirs */
        .avoir-badge {
            background-color: #ff0000;
            color: white;
            font-weight: bold;
            font-size: 12pt;
            padding: 2mm;
            text-align: center;
            margin: 2mm 0;
            border-radius: 2mm;
            text-transform: uppercase;
        }

        .reference-originale {
            background-color: #ffeeee;
            border: 1px solid #ff0000;
            padding: 2mm;
            margin: 2mm 0;
            font-size: 9pt;
            border-radius: 1mm;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .items-table th {
            padding: 1mm;
            border-bottom: 1px solid #000;
        }

        [dir="rtl"] .items-table th {
            text-align: right;
        }

        [dir="ltr"] .items-table th {
            text-align: left;
        }

        .items-table td {
            padding: 1mm 0;
            vertical-align: top;
        }

        .qty {
            width: 10%;
            text-align: center;
        }

        .description {
            width: 50%;
        }

        .payment-method {
            width: 28%;
        }

        [dir="rtl"] .price,
        [dir="rtl"] .amount {
            text-align: left;
        }

        [dir="ltr"] .price,
        [dir="ltr"] .amount {
            text-align: right;
        }

        .price {
            width: 20%;
        }

        .amount {
            width: 20%;
        }

        .summary {
            font-size: 9pt;
            margin-top: 2mm;
        }

        .summary-row {
            width: 100%;
            margin-bottom: 1mm;
        }
        .summary-row table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-row td {
            padding: 0;
        }
        .summary-row td:first-child {
            text-align: left;
        }
        .summary-row td:last-child {
            text-align: right;
        }
        .summary-row.secondary {
            font-size: 8pt;
            color: #555;
            margin-bottom: 0.5mm;
        }

        .summary-grid {
            width: 100%;
            margin-bottom: 1mm;
        }
        .summary-grid table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-grid td {
            width: 50%;
            padding: 2px 5px;
            vertical-align: top;
        }

        .total {
            font-weight: bold;
            font-size: 11pt;
            border-top: 1px solid #000;
            padding-top: 1mm;
            margin-top: 1mm;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 9pt;
            padding-top: 2mm;
            border-top: 1px dashed #000;
        }
        .img-qr-code {
            width: 100px;
            height: 100px;
        }

        .qr_code {
            margin-top: 5mm;
            margin-bottom: 3mm;
        }

        .modifiers {
            font-size: 8pt;
            color: #555;
        }

        .back-button {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            padding: 10px 20px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .back-button:hover {
            background-color: #2563eb;
        }

        /* Style pour les informations EMECEf */
        .mecef-info {
            margin-top: 3mm;
            padding: 2mm;
            border: 1px dashed #000;
            border-radius: 2mm;
            font-size: 8pt;
        }
        .mecef-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .mecef-info td {
            padding: 1mm 0;
        }
        .mecef-info td:first-child {
            font-weight: bold;
            width: 40%;
        }
        .mecef-info td:last-child {
            width: 60%;
        }

        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
            .back-button {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    @php
        $isCreditNote = $order->invoice_type === 'FA';
        $minus = $isCreditNote ? '-' : '';
    @endphp

    <!-- Back button for PWA mode -->
    <button class="back-button" onclick="goBack()" id="backButton" style="display: none;">
        ← @lang('app.back')
    </button>
    <div class="receipt">
        <div class="header">
            <div class="restaurant-name">
                @if ($receiptSettings->show_restaurant_logo)
                    @php
                        $logoUrl = restaurant()->logo_url;
                        $logoBase64 = null;
                        if ($logoUrl) {
                            try {
                                // If the URL is relative, prepend the app URL
                                if (!preg_match('/^https?:\/\//', $logoUrl)) {
                                    $logoUrl = url($logoUrl);
                                }
                                $logoImageContents = @file_get_contents($logoUrl);
                                if ($logoImageContents !== false) {
                                    $logoBase64 = 'data:image/png;base64,' . base64_encode($logoImageContents);
                                }
                            } catch (\Exception $e) {
                                $logoBase64 = null;
                            }
                        }
                    @endphp
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="{{ restaurant()->name }}" class="restaurant-logo">
                    @else
                        <img src="{{ restaurant()->logo_url }}" alt="{{ restaurant()->name }}" class="restaurant-logo">
                    @endif
                @endif
                <div>{{ restaurant()->name }}</div>
            </div>

            <div class="restaurant-info">{!! nl2br(branch()->address) !!}</div>
            <div class="restaurant-info">@lang('modules.customer.phone'):<span dir="ltr" style="unicode-bidi: embed;">{{ restaurant()->phone_number }}</span></div>
            
            <!-- Informations EMECEf (RCCM, IFU) -->
            @if(restaurant()->reg_com || restaurant()->ifu)
            <div class="mecef-info">
                <table>
                    @if(restaurant()->reg_com)
                    <tr>
                        <td>RCCM:</td>
                        <td>{{ restaurant()->reg_com }}</td>
                    </tr>
                    @endif
                    @if(restaurant()->ifu)
                    <tr>
                        <td>IFU:</td>
                        <td>{{ restaurant()->ifu }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            @endif

            @if ($receiptSettings->show_tax)
                @foreach ($taxDetails as $taxDetail)
                    <div class="restaurant-info">{{ $taxDetail->tax_name }}: {{ $taxDetail->tax_id }}</div>
                @endforeach
            @endif

        </div>

        <!-- ============================================ -->
        <!-- BADGE SPÉCIFIQUE POUR LES AVOIRS -->
        <!-- ============================================ -->
        @if($order->invoice_type == 'FA')
        <div class="avoir-badge">
            FACTURE D'AVOIR
        </div>
        
        <!-- Référence à la facture originale -->
        @if($order->original_invoice_id)
        @php
            $originalOrder = \App\Models\Order::find($order->original_invoice_id);
        @endphp
        @if($originalOrder)
        <div class="reference-originale">
            <table style="width:100%; font-size:9pt;">
                <tr>
                    <td><strong>Facture originale:</strong></td>
                    <td>FV-{{ $originalOrder->order_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Date originale:</strong></td>
                    <td>{{ $originalOrder->date_time ? $originalOrder->date_time->format('d/m/Y') : 'N/A' }}</td>
                </tr>
                @if($originalOrder->sfe_cid)
                <tr>
                    <td><strong>Code MECEF original:</strong></td>
                    <td>{{ $originalOrder->sfe_cid }}</td>
                </tr>
                @endif
            </table>
        </div>
        @endif
        @endif
        @endif

        <div class="order-info">
            <div class="">
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>
                                @if($order->invoice_type == 'FA')
                                <span class="order-number">{{ $order->order_number }}</span>
                                @else
                                <span class="order-number">{{ $order->show_formatted_order_number }}</span>
                                @endif
                            </td>
                            <td class="space_left">{{ $order->date_time->timezone(timezone())->translatedFormat('d M Y h:i A') }}</td>
                        </tr>
                    </table>
                </div>
                @php
                    $tokenNumber = $order->kot->whereNotNull('token_number')->first()?->token_number;
                @endphp
                @if ($tokenNumber)
                    <div class="summary-row">
                        <span>@lang('modules.order.tokenNumber') {{ $tokenNumber }}</span>
                    </div>
                @endif
                
                <!-- Code vendeur (celui qui a vendu) -->
                @if($order->waiter && $order->waiter->name)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>@lang('modules.order.waiter'):</td>
                            <td>{{ $order->waiter->name }}</td>
                        </tr>
                    </table>
                </div>
                @endif

                @if($receiptSettings->show_table_number || $receiptSettings->show_total_guest)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>
                                @if ($receiptSettings->show_table_number && $order->table && $order->table->table_code)
                                    @lang('modules.settings.tableNumber'): {{ $order->table->table_code }}
                                @endif
                            </td>
                            <td>
                                @if ($receiptSettings->show_total_guest && $order->number_of_pax)
                                    @lang('modules.order.noOfPax'): {{ $order->number_of_pax }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
                @endif
                
                @if ($receiptSettings->show_order_type )
                    <div class="summary-row">
                            <span> {{ Str::title(ucwords(str_replace('_', ' ', $order->order_type))) }}
                                @if ($order->order_type === 'pickup')
                                    @if ($order->pickup_date)
                                        <span class="">
                                            : {{ \Carbon\Carbon::parse($order->pickup_date)->translatedFormat('d M Y h:i A') }}
                                        </span>
                                    @endif
                                @endif
                            </span>
                    </div>
                @endif
                
                @if ($receiptSettings->show_customer_name && $order->customer && $order->customer->name)
                    <div class="summary-row">
                        <span class="showData">@lang('modules.customer.customer'): <span class="">{{ $order->customer->name }}</span></span>
                    </div>
                @endif

                @if ($receiptSettings->show_customer_address && $order->customer && $order->customer->delivery_address)
                    <div class="summary-row">
                        <span>@lang('modules.customer.customerAddress'): <span class="">{{ $order->customer->delivery_address }}</span></span>
                    </div>
                @endif

                @if ($receiptSettings->show_customer_phone && $order->customer && $order->customer->phone)
                    <div class="summary-row">
                        <span>@lang('modules.customer.phone'): <span dir="ltr" style="unicode-bidi: embed;">{{ $order->customer->phone }}</span></span>
                    </div>
                @endif
                
                <!-- Raison du retour (si c'est un avoir) -->
                @if($order->invoice_type == 'FA' && !empty($order->note))
                <div class="summary-row" style="margin-top: 2mm; color: #ff0000;">
                    <table>
                        <tr>
                            <td><strong>Raison du retour:</strong></td>
                            <td>{{ $order->note }}</td>
                        </tr>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th class="qty">@lang('modules.order.qty')</th>
                    <th class="description">@lang('modules.menu.itemName')</th>
                    <th class="price">@lang('modules.order.price')</th>
                    <th class="amount">@lang('modules.order.amount')</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    @php
                        $itemTaxes = [];
                        $totalItemTax = 0;

                        if ($item->menuItem && $item->menuItem->taxes) {
                            foreach ($item->menuItem->taxes as $tax) {

                                // Prix HT unitaire
                                $priceHT = $item->price / (1 + ($tax->tax_percent / 100));

                                // Taxe unitaire
                                $taxAmountUnit = $item->price - $priceHT;

                                // Taxe totale
                                $taxAmount = $taxAmountUnit * $item->quantity;

                                $itemTaxes[] = [
                                    'name' => $tax->tax_name,
                                    'rate' => $tax->tax_percent,
                                    'amount' => $taxAmount
                                ];

                                $totalItemTax += $taxAmount;
                            }
                        }

                        $priceHT = $item->price - ($totalItemTax / $item->quantity);
                        $amountHT = $priceHT * $item->quantity;
                        
                        // Pour les avoirs, on peut ajouter un indicateur visuel
                        $isReturn = $order->invoice_type == 'FA';
                    @endphp
                    <tr @if($isReturn) style="color: #ff0000;" @endif>
                        <td class="qty">{{ $minus }}{{ $item->quantity }}</td>
                        <td class="description">
                            {{ $item->menuItem->item_name }}
                            @if($isReturn)
                                <br><small style="color: #ff0000; font-weight: bold;">(RETOUR)</small>
                            @endif
                            @if(!empty($itemTaxes))
                                <br>
                                @foreach($itemTaxes as $tax)
                                    <small style="color: #666;">({{ $tax['name'] }} {{ $tax['rate'] }}%)</small>
                                @endforeach
                            @endif

                            @if (isset($item->menuItemVariation))
                                <br><small>({{ $item->menuItemVariation->variation }})</small>
                            @endif
                            @foreach ($item->modifierOptions as $modifier)
                                @php
                                    if ($order->order_type_id) {
                                        $modifier->setPriceContext($order->order_type_id, $order?->delivery_app_id);
                                    }
                                @endphp
                                <div class="modifiers">• {{ $modifier->name ?? $modifier->pivot->modifier_option_name }}
                                    (+{{ currency_format($modifier->pivot->modifier_option_price ?? $modifier->price, restaurant()->currency_id) }})
                                </div>
                            @endforeach
                        </td>
                        <td class="price">
                            {{ $item->price }}
                            <br><small style="color: #666;">HT: {{ currency_format($priceHT, restaurant()->currency_id) }}</small>
                        </td>
                        <td class="amount">
                            {{ $minus }}{{ currency_format($item->amount, restaurant()->currency_id) }}
                            @if($totalItemTax > 0)
                                <br><small style="color: #666;">Taxe: {{ $minus }}{{ currency_format($totalItemTax, restaurant()->currency_id) }}</small>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            <div class="summary-row">
                <table>
                    <tr>
                        <td>@lang('modules.order.subTotal'):</td>
                        <td>{{ $minus }}{{ currency_format($order->sub_total, restaurant()->currency_id) }}</td>
                    </tr>
                </table>
            </div>

            @if (!is_null($order->discount_amount))
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>@lang('modules.order.discount') @if ($order->discount_type == 'percent')
                                    ({{ rtrim(rtrim($order->discount_value, '0'), '.') }}%)
                                @endif
                            </td>
                            <td>-{{ currency_format($order->discount_amount, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            @foreach ($order->charges as $item)
            <div class="summary-row">
                <table>
                    <tr>
                        <td>{{ $item->charge->charge_name }}
                            @if ($item->charge->charge_type == 'percent')
                            ({{ $item->charge->charge_value }}%)
                            @endif:
                        </td>
                        <td>
                            {{ $minus }}{{ currency_format(($item->charge->getAmount($order->sub_total - ($order->discount_amount ?? 0))), restaurant()->currency_id) }}
                        </td>
                    </tr>
                </table>
            </div>
            @endforeach

            @if ($order->tip_amount > 0)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>@lang('modules.order.tip'):</td>
                            <td>{{ currency_format($order->tip_amount, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            <!-- SOMME DES TAXES -->
            @php
                $totalOtherTaxes = 0;
                $taxSummary = [];
                
                foreach ($order->items as $item) {
                    if ($item->menuItem && $item->menuItem->taxes) {
                        foreach ($item->menuItem->taxes as $tax) {
                            $taxAmount = ($tax->tax_percent / 100) * $item->price * $item->quantity;
                            $totalOtherTaxes += $taxAmount;
                            
                            // Pour le résumé par taxe
                            $taxName = $tax->tax_name;
                            if (!isset($taxSummary[$taxName])) {
                                $taxSummary[$taxName] = [
                                    'rate' => $tax->tax_percent,
                                    'amount' => $taxAmount
                                ];
                            } else {
                                $taxSummary[$taxName]['amount'] += $taxAmount;
                            }
                        }
                    }
                }
            @endphp
            
            <!-- @if($totalOtherTaxes > 0)
                @foreach($taxSummary as $taxName => $taxData)
                <div class="summary-row secondary">
                    <table>
                        <tr>
                            <td>{{ $taxName }} ({{ $taxData['rate'] }}%):</td>
                            <td>{{ currency_format($taxData['amount'], restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
                @endforeach
            @endif -->

            @if ($order->order_type === 'delivery' && !is_null($order->delivery_fee))
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>@lang('modules.delivery.deliveryFee')</td>
                            <td>
                                @if($order->delivery_fee > 0)
                                    {{ currency_format($order->delivery_fee, restaurant()->currency_id) }}
                                @else
                                    @lang('modules.delivery.freeDelivery')
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

            @if ($taxMode == 'order')
                @foreach ($order->taxes as $item)
                    <div class="summary-row">
                        <table>
                            <tr>
                                <td>{{ $item->tax->tax_name }} ({{ $item->tax->tax_percent }}%):</td>
                                <td>{{ currency_format(($item->tax->tax_percent / 100) * ($order->sub_total - ($order->discount_amount ?? 0)), restaurant()->currency_id) }}</td>
                            </tr>
                        </table>
                    </div>
                @endforeach
            @else
                @if($order->total_tax_amount > 0)
                    @php
                        $taxTotals = [];
                        $totalTax = 0;
                        foreach ($order->items as $item) {
                            $qty = $item->quantity ?? 1;
                            $taxBreakdown = is_array($item->tax_breakup) ? $item->tax_breakup : (json_decode($item->tax_breakup, true) ?? []);
                            foreach ($taxBreakdown as $taxName => $taxInfo) {
                                if (!isset($taxTotals[$taxName])) {
                                    $taxTotals[$taxName] = [
                                        'percent' => $taxInfo['percent'] ?? 0,
                                        'amount' => ($taxInfo['amount'] ?? 0) * $qty
                                    ];
                                } else {
                                    $taxTotals[$taxName]['amount'] += ($taxInfo['amount'] ?? 0) * $qty;
                                }
                            }
                            $totalTax += $item->tax_amount ?? 0;
                        }
                    @endphp
                    <div>
                        @foreach ($taxTotals as $taxName => $taxInfo)
                        <div class="summary-row secondary">
                            <table>
                                <tr>
                                    <td>{{ $taxName }} ({{ $taxInfo['percent'] }}%)</td>
                                    <td>{{ $minus }}{{ currency_format($taxInfo['amount'], restaurant()->currency_id) }}</td>
                                </tr>
                            </table>
                        </div>
                        @endforeach
                    </div>
                    <div class="summary-row">
                        <table>
                            <tr>
                                <td>@lang('modules.order.totalTax'):</td>
                                <td>{{ currency_format($totalTax, restaurant()->currency_id) }}</td>
                            </tr>
                        </table>
                    </div>
                @endif
            @endif

            @if ($payment)
                <div class="summary-row">
                    <table>
                        <tr>
                            <td>@lang('modules.order.balanceReturn'):</td>
                            <td>{{ currency_format($payment->balance, restaurant()->currency_id) }}</td>
                        </tr>
                    </table>
                </div>
            @endif

            <div class="summary-row total">
                <table>
                    <tr>
                        <td>@if($order->invoice_type == 'FA')<span style="color: #ff0000;">TOTAL:</span>@else @lang('modules.order.total'): @endif</td>
                        <td @if($order->invoice_type == 'FA') style="color: #ff0000; font-weight: bold;" @endif>{{ $minus }}{{ currency_format($order->total, restaurant()->currency_id) }}</td>
                    </tr>
                </table>
            </div>

            @if ($receiptSettings->show_payment_status)
                <div class="summary-row" style="margin-top: 2mm; padding-top: 2mm; border-top: 1px dashed #000;">
                    <table>
                        <tr>
                            <td style="font-weight: bold;">@lang('modules.order.paymentStatus'):</td>
                            <td style="font-weight: bold;">
                                @if($order->status === 'paid')
                                    <span style="color: #10b981;">@lang('modules.order.paid')</span>
                                @else
                                    <span style="color: #ef4444;">@lang('modules.order.unpaid')</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            @endif

        </div>

        <div class="footer">
            <p>@lang('messages.thankYouVisit')</p>

            @if ($order->status != 'paid')
            <div>
                @if ($receiptSettings->show_payment_qr_code)
                    <p class="qr_code">@lang('modules.settings.payFromYourPhone')</p>
                    @php
                        // Get the QR code image and convert to base64
                        $qrCodeUrl = $receiptSettings->payment_qr_code_url;
                        $qrCodeBase64 = null;
                        if ($qrCodeUrl) {
                            try {
                                // If the URL is relative, prepend the app URL
                                if (!preg_match('/^https?:\/\//', $qrCodeUrl)) {
                                    $qrCodeUrl = url($qrCodeUrl);
                                }
                                $qrImageContents = @file_get_contents($qrCodeUrl);
                                if ($qrImageContents !== false) {
                                    $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($qrImageContents);
                                }
                            } catch (\Exception $e) {
                                $qrCodeBase64 = null;
                            }
                        }
                    @endphp
                    @if ($qrCodeBase64)
                        <img class="qr-code-img" src="{{ $qrCodeBase64 }}" alt="QR Code">
                    @else
                        <img class="qr-code-img" src="{{ $receiptSettings->payment_qr_code_url }}" alt="QR Code">
                    @endif
                    <p class="">@lang('modules.settings.scanQrCode')</p>
                @endif
            </div>
            @endif

            @if ($receiptSettings->show_payment_details && $order->payments->count())
                <div class="summary">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th class="qty" style="text-align: center">@lang('modules.order.amount')</th>
                              @if(!$isCreditNote)<th class="payment-method" style="text-align: center">@lang('modules.order.paymentMethod')</th>@endif
                                <th class="price" style="text-align: center">@lang('app.dateTime')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->payments as $payment)
                                <tr>
                                    <td class="qty" style="text-align: center">{{ $minus }}{{ currency_format($payment->amount, restaurant()->currency_id) }}</td>
                                    @if(!$isCreditNote)<td class="payment-method" style="text-align: center">@lang('modules.order.' . $payment->payment_method)</td>@endif
                                    <td class="price" style="text-align: center">
                                        @if($payment->payment_method != 'due')
                                            {{ $payment->created_at->timezone(timezone())->translatedFormat('d M Y h:i A') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            


            <!-- ============================================ -->
            <!-- SECTION MECEF DGI -->
            <!-- ============================================ -->
            @if(!empty($order->sfe_uid) && $order->sfe_status == 1)
            <div style="margin-top: 3mm; padding: 1.5mm; border: 1px solid #333; border-radius: 1.5mm; page-break-inside: avoid; font-size: 7pt;">
                
                <div style="text-align: center; font-weight: bold; font-size: 8pt; margin-bottom: 1mm;">
                    CODE MECef/DGI: {{ $order->sfe_cid ?? 'N/A' }}
                </div>
                
                <table style="width: 100%; border-collapse: collapse; font-size: 7pt;">
                    <tr>
                        <td style="padding: 0.5mm 0; width: 35%;">NIM:</td>
                        <td style="padding: 0.5mm 0; width: 65%;">{{ $order->sfe_nim ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5mm 0;">COMPTEURS:</td>
                        <td style="padding: 0.5mm 0;">{{ $order->sfe_counters ?? 'N/A' }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5mm 0;">DATE & HEURE </td>
                        <td style="padding: 0.5mm 0;">{{ $order->sfe_datetime ? date('d/m/Y H:i', strtotime($order->sfe_datetime)) : 'N/A' }}</td>
                    </tr>
                </table>
                
                <!-- QR CODE MECEF compact -->
                @if(!empty($order->sfe_qrcode))
                <div style="margin-top: 1.5mm; text-align: center;">
                    @php
                        $qrSize = 80;
                        $qrText = urlencode($order->sfe_qrcode);
                    @endphp

                    <img src="https://api.qrserver.com/v1/create-qr-code/?size={{ $qrSize }}x{{ $qrSize }}&data={{ $qrText }}" 
                         alt="QR MECEF" 
                         style="width: {{ $qrSize }}px; height: {{ $qrSize }}px; margin: 0 auto; image-rendering: pixelated;">
                </div>
                @endif
                
                <div style="margin-top: 1mm; text-align: center; font-size: 6pt; color: #777;">
                    Facture normalisée
                </div>
                
            </div>
            @endif

            <!-- Mention spécifique pour les avoirs -->
            @if($order->invoice_type == 'FA')
            <div style="margin-top: 2mm; font-size: 8pt; color: #ff0000; text-align: center;">
                <strong>Ce document annule et remplace la facture originale.</strong>
            </div>
            @endif

        </div>

    </div>

    <script>
        // Detect if running in PWA standalone mode
        function isPWA() {
            return (window.matchMedia('(display-mode: standalone)').matches) || 
                   (window.navigator.standalone === true) ||
                   (document.referrer.includes('android-app://'));
        }

        // Show back button if in PWA mode
        if (isPWA()) {
            const backButton = document.getElementById('backButton');
            if (backButton) {
                backButton.style.display = 'block';
            }
        }

        // Go back function
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // If no history, redirect to orders page or home
                window.location.href = '{{ route("orders.index") }}';
            }
        }

        // Auto-trigger print dialog when page loads and close the window afterward
        window.onload = function() {
            const closeAfterPrint = () => {
                // In PWA, navigate back instead of trying to close the window
                if (isPWA()) {
                    goBack();
                } else {
                    window.close();
                }
            };

            // Set handler for after print where supported
            if ('onafterprint' in window) {
                window.onafterprint = function() {
                    closeAfterPrint();
                };
            } else {
                // Fallback: attempt to close shortly after print is triggered
                setTimeout(closeAfterPrint, 1000);
            }

            window.print();
        };
    </script>
</body>

</html>