<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\KotItem;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RemovedKotItemReportExport implements WithMapping, FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    protected $startDateTime;
    protected $endDateTime;
    protected $timezone;
    protected $offset;
    protected $selectedCancelReason;
    protected $selectedWaiter;
    protected $currencyId;

    public function __construct($startDateTime, $endDateTime, $timezone, $offset, $selectedCancelReason = '', $selectedWaiter = '', $currencyId = null)
    {
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->timezone = $timezone;
        $this->offset = $offset;
        $this->selectedCancelReason = $selectedCancelReason;
        $this->selectedWaiter = $selectedWaiter;
        $this->currencyId = $currencyId;
    }

    public function headings(): array
    {
        $startDate = Carbon::parse($this->startDateTime)->format('m/d/Y');
        $endDate = Carbon::parse($this->endDateTime)->format('m/d/Y');

        return [
            ['Removed KOT Item Report - ' . $startDate . ' to ' . $endDate],
            [
                'KOT Number',
                'Order Number',
                'Removed By',
                'Removed By Email',
                'Item Name',
                'Quantity',
                'Table',
                'Cancellation Reason',
                'Custom Reason',
                'Removed Date',
                'Total Price',
            ]
        ];
    }

    public function map($item): array
    {
        // Try to get item name from menuItem, then orderItem, then direct query
        $itemName = 'N/A';

        // First try: menuItem relationship
        if ($item->menuItem) {
            $itemName = $item->menuItem->item_name ?? 'N/A';
        }
        // Second try: orderItem relationship
        elseif ($item->orderItem && $item->orderItem->menuItem) {
            $itemName = $item->orderItem->menuItem->item_name ?? 'N/A';
        }
        // Third try: direct query if menu_item_id exists
        elseif (isset($item->menu_item_id) && $item->menu_item_id) {
            try {
                $menuItem = \App\Models\MenuItem::withoutGlobalScopes()->find($item->menu_item_id);
                if ($menuItem) {
                    $itemName = $menuItem->item_name ?? 'N/A';
                }
            } catch (\Exception $e) {
                // If query fails, keep N/A
            }
        }

        // Try to get price from menuItemVariation, then menuItem, then orderItem
        $unitPrice = 0;
        if ($item->menuItemVariation) {
            $unitPrice = $item->menuItemVariation->price ?? 0;
        } elseif ($item->menuItem) {
            $unitPrice = $item->menuItem->price ?? 0;
        } elseif ($item->orderItem && $item->orderItem->menuItem) {
            $unitPrice = $item->orderItem->menuItem->price ?? 0;
        }

        $totalPrice = $unitPrice * $item->quantity;
        $removedDate = $item->updated_at ? Carbon::parse($item->updated_at)->setTimezone($this->timezone)->format('M d, Y h:i A') : 'N/A';

        // Use cancelledBy if available, otherwise fallback to waiter for old records
        $removedByUser = $item->cancelledBy;
        if (!$removedByUser && $item->kot && $item->kot->order && $item->kot->order->waiter) {
            $removedByUser = $item->kot->order->waiter;
        }

        return [
            $item->kot->kot_number ?? 'N/A',
            $item->kot && $item->kot->order ? ($item->kot->order->show_formatted_order_number ?? '#' . $item->kot->order->order_number) : 'N/A',
            $removedByUser ? $removedByUser->name : 'N/A',
            $removedByUser ? ($removedByUser->email ?? 'N/A') : 'N/A',
            $itemName,
            $item->quantity,
            ($item->kot && $item->kot->order && $item->kot->order->table)
                ? ($item->kot->order->table->table_code ?? 'N/A')
                : (($item->kot && $item->kot->table)
                    ? ($item->kot->table->table_code ?? 'N/A')
                    : 'N/A'),
            $item->cancelReason->reason ?? 'N/A',
            $item->cancel_reason_text ?? 'N/A',
            $removedDate,
            currency_format($totalPrice, $this->currencyId),
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
        $query = KotItem::withoutGlobalScopes()
            ->with([
                'menuItem' => function($q) {
                    $q->withoutGlobalScopes();
                },
                'menuItemVariation',
                'kot.order.customer',
                'kot.order.table',
                'kot.order.waiter',
                'kot.table',
                'kot',
                'cancelReason',
                'cancelledBy',
                'orderItem' => function($q) {
                    $q->with(['menuItem' => function($subQ) {
                        $subQ->withoutGlobalScopes();
                    }]);
                }
            ])
            ->where('status', 'cancelled')
            ->where(function($q) {
                // Only show items that were intentionally removed (have a cancel reason)
                $q->whereNotNull('cancel_reason_id')
                  ->orWhereNotNull('cancel_reason_text');
            })
            ->whereHas('kot.order', function ($q) {
                $q->where('branch_id', branch()->id);
            })
            ->whereBetween('updated_at', [$this->startDateTime, $this->endDateTime]);

        if ($this->selectedCancelReason) {
            $query->where('cancel_reason_id', $this->selectedCancelReason);
        }

        if ($this->selectedWaiter) {
            $query->whereHas('kot.order', function ($q) {
                $q->where('waiter_id', $this->selectedWaiter);
            });
        }

        return $query->orderBy('updated_at', 'desc')->get();
    }
}

