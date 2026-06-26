<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Order;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CancelledOrderReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $startDateTime;
    protected $endDateTime;
    protected $timezone;
    protected $offset;
    protected $selectedCancelReason;
    protected $selectedCancelledBy;
    protected $currencyId;

    public function __construct($startDateTime, $endDateTime, $timezone, $offset, $selectedCancelReason = '', $selectedCancelledBy = '', $currencyId = null)
    {
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->timezone = $timezone;
        $this->offset = $offset;
        $this->selectedCancelReason = $selectedCancelReason;
        $this->selectedCancelledBy = $selectedCancelledBy;
        $this->currencyId = $currencyId;
    }

    public function headings(): array
    {
        $startDate = Carbon::parse($this->startDateTime)->format('m/d/Y');
        $endDate = Carbon::parse($this->endDateTime)->format('m/d/Y');

        return [
            ['Cancelled Order Report - ' . $startDate . ' to ' . $endDate],
            [
                'Order Number',
                'Order Date',
                'Cancelled Date',
                'Customer',
                'Customer Phone',
                'Table',
                'Waiter',
                'Cancellation Reason',
                'Custom Reason',
                'Cancelled By',
                'Cancelled By Email',
                'Order Total',
            ]
        ];
    }

    public function map($order): array
    {
        $orderDate = $order->date_time ? Carbon::parse($order->date_time)->setTimezone($this->timezone)->format('M d, Y h:i A') : 'N/A';
        $cancelledDate = $order->updated_at ? Carbon::parse($order->updated_at)->setTimezone($this->timezone)->format('M d, Y h:i A') : 'N/A';

        return [
            $order->show_formatted_order_number ?? '#' . $order->order_number,
            $orderDate,
            $cancelledDate,
            $order->customer->name ?? 'Walk-in',
            $order->customer->phone ?? 'N/A',
            $order->table->name ?? 'N/A',
            $order->waiter->name ?? 'N/A',
            $order->cancelReason->reason ?? 'N/A',
            $order->cancel_reason_text ?? 'N/A',
            $order->cancelledBy->name ?? 'N/A',
            $order->cancelledBy->email ?? 'N/A',
            currency_format($order->total, $this->currencyId),
        ];
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return $defaultStyle->getFont()->setName('Arial');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'name' => 'Arial', 'size' => 14], 'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f5f5f5'],
            ]],
            2 => ['font' => ['bold' => true, 'name' => 'Arial'], 'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'e5e5e5'],
            ]],
        ];
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Order::with(['customer', 'cancelReason', 'cancelledBy', 'table', 'waiter'])
            ->where('status', 'canceled')
            ->where('order_status', 'cancelled')
            ->whereBetween('updated_at', [$this->startDateTime, $this->endDateTime]);

        if ($this->selectedCancelReason) {
            $query->where('cancel_reason_id', $this->selectedCancelReason);
        }

        if ($this->selectedCancelledBy) {
            $query->where('cancelled_by', $this->selectedCancelledBy);
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }
}

