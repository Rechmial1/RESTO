<?php

namespace App\Exports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TaxReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected string $exportType;
    protected array $data;
    protected string $startDate;
    protected string $endDate;
    protected string $startTime;
    protected string $endTime;
    protected string $timezone;
    protected $currencyId;

    public function __construct(string $exportType, array $data, string $startDate, string $endDate, string $startTime, string $endTime, string $timezone)
    {
        $this->exportType = $exportType;
        $this->data = $data;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->timezone = $timezone;
        $this->currencyId = restaurant()->currency_id;
    }

    public function headings(): array
    {
        $headingTitle = $this->startDate === $this->endDate
            ? __('modules.report.salesDataFor') . " {$this->startDate}, " . __('modules.report.timePeriod') . " {$this->startTime} - {$this->endTime}"
            : __('modules.report.salesDataFrom') . " {$this->startDate} " . __('app.to') . " {$this->endDate}, " . __('modules.report.timePeriodEachDay') . " {$this->startTime} - {$this->endTime}";

        switch ($this->exportType) {
            case 'byTaxType':
                return [
                    [__('menu.taxReport') . ' - Tax Breakdown by Tax Type ' . $headingTitle],
                    [
                        __('modules.tax.taxName'),
                        __('modules.tax.taxPercent'),
                        __('modules.report.totalTaxAmount'),
                        __('modules.report.itemsCount'),
                        __('modules.report.ordersCount'),
                    ]
                ];

            case 'byDate':
                return [
                    [__('menu.taxReport') . ' - Tax Breakdown by Date ' . $headingTitle],
                    [
                        __('app.date'),
                        __('modules.report.totalTaxAmount'),
                        __('modules.report.totalRevenue'),
                        __('modules.report.totalOrders'),
                        __('modules.report.itemsCount'),
                        __('modules.report.taxBreakdown'),
                    ]
                ];

            case 'byOrder':
                return [
                    [__('menu.taxReport') . ' - Tax Details by Order ' . $headingTitle],
                    [
                        __('modules.order.orderNumber'),
                        __('app.dateTime'),
                        __('modules.order.subtotal'),
                        __('modules.report.taxBreakdown'),
                        __('modules.report.totalTaxAmount'),
                        __('modules.order.total'),
                    ]
                ];

            default:
                return [];
        }
    }

    public function map($item): array
    {
        switch ($this->exportType) {
            case 'byTaxType':
                return [
                    $item['name'],
                    number_format($item['percent'], 2) . '%',
                    currency_format($item['total_amount'], $this->currencyId),
                    $item['items_count'],
                    $item['orders_count'],
                ];

            case 'byDate':
                $taxBreakdown = '';
                if (isset($item['tax_breakdown']) && count($item['tax_breakdown']) > 0) {
                    $breakdowns = [];
                    foreach ($item['tax_breakdown'] as $taxName => $taxInfo) {
                        $breakdowns[] = "{$taxName} (" . number_format($taxInfo['percent'], 2) . "%): " . currency_format($taxInfo['amount'], $this->currencyId);
                    }
                    $taxBreakdown = implode('; ', $breakdowns);
                }
                return [
                    $item['formatted_date'],
                    currency_format($item['total_tax'], $this->currencyId),
                    currency_format($item['total_revenue'], $this->currencyId),
                    $item['total_orders'],
                    $item['total_items'],
                    $taxBreakdown ?: '-',
                ];

            case 'byOrder':
                $taxBreakdown = '';
                if (isset($item['tax_breakdown']) && count($item['tax_breakdown']) > 0) {
                    $breakdowns = [];
                    foreach ($item['tax_breakdown'] as $taxName => $taxInfo) {
                        $breakdowns[] = "{$taxName} (" . number_format($taxInfo['percent'], 2) . "%): " . currency_format($taxInfo['amount'], $this->currencyId);
                    }
                    $taxBreakdown = implode('; ', $breakdowns);
                }
                return [
                    '#' . $item['order']->order_number,
                    Carbon::parse($item['order']->date_time)->format('M d, Y h:i A'),
                    currency_format($item['subtotal'], $this->currencyId),
                    $taxBreakdown ?: '-',
                    currency_format($item['tax_amount'], $this->currencyId),
                    currency_format($item['total'], $this->currencyId),
                ];

            default:
                return [];
        }
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle
            ->getFont()
            ->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f5f5f5'],
            ]],
            2 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f5f5f5'],
            ]],
        ];
    }

    public function collection()
    {
        return collect($this->data);
    }
}

