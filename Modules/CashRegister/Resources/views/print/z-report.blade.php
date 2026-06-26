<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ isRtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ restaurant()->name }} - Z-Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }

        [dir="rtl"] {
            text-align: right;
        }

        [dir="ltr"] {
            text-align: left;
        }

        .receipt {
            width: {{ ($width ?? 80) - 5 }}mm;
            padding: {{ $thermal ? '1mm' : '6.35mm' }};
            page-break-after: always;
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .restaurant-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .report-title {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2mm;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 2mm 0;
        }

        .info-section {
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .info-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        .financial-section {
            margin-bottom: 3mm;
            font-size: 9pt;
        }

        .financial-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1mm;
        }

        .total-line {
            font-weight: bold;
            border-top: 1px solid #000;
            padding-top: 1mm;
            margin-top: 1mm;
            font-size: 11pt;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 9pt;
            padding-top: 2mm;
            border-top: 1px dashed #000;
        }

        @media print {
            @page {
                margin: 0;
                size: 80mm auto;
            }
        }
    </style>
</head>

<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="restaurant-name">{{ restaurant()->name }}</div>
            <div class="report-title">@lang('cashregister::app.zReport')</div>
        </div>

        <div class="separator"></div>

        <!-- Report Information -->
        <div class="info-section">
            <div class="info-line">
                <span>@lang('cashregister::app.generatedOn')</span>
                <span>{{ $reportData['generated_at']->timezone(timezone())->format('Y-m-d H:i') }}</span>
            </div>
            <div class="info-line">
                <span>@lang('cashregister::app.branch')</span>
                <span>{{ $reportData['session']->branch->name ?? 'N/A' }}</span>
            </div>
            <div class="info-line">
                <span>@lang('cashregister::app.register')</span>
                <span>{{ $reportData['session']->register->name ?? 'N/A' }}</span>
            </div>
            <div class="info-line">
                <span>@lang('cashregister::app.cashier')</span>
                <span>{{ $reportData['session']->cashier->name ?? 'N/A' }}</span>
            </div>
            @if($reportData['session']->closed_at)
            <div class="info-line">
                <span>@lang('cashregister::app.closed')</span>
                <span>{{ $reportData['session']->closed_at->format('Y-m-d H:i:s') }}</span>
            </div>
            @endif
        </div>

        <div class="separator"></div>

        <!-- Financial Data -->
        <div class="financial-section">
            <div class="financial-line">
                <span>@lang('cashregister::app.openingFloat')</span>
                <span>{{ currency_format($reportData['opening_float'], restaurant()->currency_id) }}</span>
            </div>
            <div class="financial-line">
                <span>@lang('cashregister::app.cashSales')</span>
                <span>{{ currency_format($reportData['cash_sales'], restaurant()->currency_id) }}</span>
            </div>
            <div class="financial-line">
                <span>@lang('cashregister::app.cashIn')</span>
                <span>{{ currency_format($reportData['cash_in'], restaurant()->currency_id) }}</span>
            </div>
            <div class="financial-line">
                <span>@lang('cashregister::app.cashOut')</span>
                <span>{{ currency_format($reportData['cash_out'], restaurant()->currency_id) }}</span>
            </div>
            <div class="financial-line">
                <span>@lang('cashregister::app.safeDrops')</span>
                <span>{{ currency_format($reportData['safe_drops'], restaurant()->currency_id) }}</span>
            </div>
            <div class="financial-line">
                <span>@lang('cashregister::app.refunds')</span>
                <span>{{ currency_format($reportData['refunds'], restaurant()->currency_id) }}</span>
            </div>
            @if(isset($reportData['actual_cash']))
            <div class="financial-line">
                <span>@lang('cashregister::app.actualCash')</span>
                <span>{{ currency_format($reportData['actual_cash'], restaurant()->currency_id) }}</span>
            </div>
            @endif
            @if(isset($reportData['discrepancy']))
            <div class="financial-line">
                <span>@lang('cashregister::app.discrepancy')</span>
                <span>{{ currency_format($reportData['discrepancy'], restaurant()->currency_id) }}</span>
            </div>
            @endif
        </div>

        <!-- Expected Cash Total -->
        <div class="financial-line total-line">
            <span>@lang('cashregister::app.expectedCash')</span>
            <span>{{ currency_format($reportData['expected_cash'], restaurant()->currency_id) }}</span>
        </div>

        <div class="separator"></div>

        <!-- Counted Cash (Denominations) -->
        @if(isset($denominations) && $denominations->count() > 0)
        @php
            $grouped = $denominations->groupBy('cash_denomination_id')->map(function($items) {
                return [
                    'value' => optional($items->first()->denomination)->value,
                    'count' => $items->sum('count'),
                    'subtotal' => $items->sum('subtotal'),
                ];
            })->sortByDesc('value');
        @endphp
        <div class="denominations-section">
            <div class="section-title">@lang('cashregister::app.countedCash') (@lang('cashregister::app.denominations'))</div>
            @foreach($grouped as $row)
                <div class="financial-line">
                    <span>{{ currency_format((float) $row['value'], restaurant()->currency_id) }} × {{ $row['count'] }}</span>
                    <span>{{ currency_format((float) $row['subtotal'], restaurant()->currency_id) }}</span>
                </div>
            @endforeach
            <div class="financial-line total-line">
                <span>@lang('cashregister::app.totalCounted')</span>
                <span>{{ currency_format($reportData['counted_cash'], restaurant()->currency_id) }}</span>
            </div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <div>@lang('cashregister::app.thankYou')</div>
        </div>
    </div>
    <script>
        window.onload = function() {
            try { window.print(); } catch (e) { /* noop */ }
        }
    </script>
</body>

</html>
