<?php

namespace App\Livewire\Billing;

use App\Models\GlobalInvoice;
use Livewire\Component;

class InvoiceList extends Component
{
    public $search;

    public function getGlobalEarningsStatistics()
    {
        $invoices = GlobalInvoice::with('currency')->get();

        $totalInvoices = $invoices->count();
        $totalAmount = $invoices->sum('total') ?? 0;

        // Calculate monthly earnings
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEarnings = GlobalInvoice::where('pay_date', '>=', $currentMonthStart)
            ->sum('total') ?? 0;

        // Calculate last month earnings
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();
        $lastMonthEarnings = GlobalInvoice::whereBetween('pay_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('total') ?? 0;

        // Get first and last payment dates
        $firstPayment = GlobalInvoice::orderBy('pay_date')->first();
        $lastPayment = GlobalInvoice::orderByDesc('pay_date')->first();

        // Calculate unique restaurants count
        $totalRestaurants = GlobalInvoice::distinct('restaurant_id')->count('restaurant_id');

        return [
            'total_invoices' => $totalInvoices,
            'total_amount' => $totalAmount,
            'current_month_earnings' => $currentMonthEarnings,
            'last_month_earnings' => $lastMonthEarnings,
            'first_payment_date' => $firstPayment?->pay_date,
            'last_payment_date' => $lastPayment?->pay_date,
            'total_restaurants' => $totalRestaurants,
        ];
    }

    public function render()
    {
        $earningsStats = $this->getGlobalEarningsStatistics();

        return view('livewire.billing.invoice-list', [
            'earningsStats' => $earningsStats
        ]);
    }
}
