<?php

namespace App\Livewire\Dashboard;

use App\Models\RestaurantPayment;
use App\Models\GlobalSubscription;
use App\Models\GlobalInvoice;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class SuperadminRevenueChart extends Component
{
    public $monthlyRevenue;
    public $percentChange;
    public $revenueData;
    public $topRestaurants;
    public $subscriptionStats;

    public function mount()
    {
        $this->calculateRevenueMetrics();
        $this->getTopRestaurants();
        $this->getSubscriptionStats();
    }

    private function calculateRevenueMetrics()
    {
        $startOfMonth = now()->startOfMonth()->startOfDay()->toDateTimeString();
        $tillToday = now()->endOfDay()->toDateTimeString();

        $startOfLastMonth = now()->subMonth()->startOfMonth()->startOfDay()->toDateTimeString();
        $endOfLastMonth = now()->subMonth()->endOfMonth()->endOfDay()->toDateTimeString();

        // Current month revenue from global invoices
        $this->monthlyRevenue = GlobalInvoice::whereDate('pay_date', '>=', $startOfMonth)
            ->whereDate('pay_date', '<=', $tillToday)

            ->sum('total');

        // Previous month revenue
        $previousRevenue = GlobalInvoice::whereDate('pay_date', '>=', $startOfLastMonth)
            ->whereDate('pay_date', '<=', $endOfLastMonth)
            ->sum('total');

        // Calculate percentage change
        $revenueDifference = ($this->monthlyRevenue - $previousRevenue);
        $this->percentChange = ($revenueDifference / ($previousRevenue == 0 ? 1 : $previousRevenue)) * 100;

        // Get revenue data for chart (last 6 months)
        $this->revenueData = GlobalInvoice::select(
            DB::raw('DATE_FORMAT(pay_date, "%Y-%m") as month'),
            DB::raw('SUM(total) as total_revenue')
        )
            ->where('status', 'paid')
            ->where('pay_date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();
    }

    private function getTopRestaurants()
    {
        $this->topRestaurants = GlobalInvoice::select(
            'restaurants.name',
            DB::raw('SUM(global_invoices.total) as total_payments'),
            DB::raw('COUNT(global_invoices.id) as payment_count')
        )
            ->join('restaurants', 'global_invoices.restaurant_id', '=', 'restaurants.id')
            ->where('global_invoices.status', 'paid')
            ->where('global_invoices.pay_date', '>=', now()->startOfMonth())
            ->groupBy('restaurants.id', 'restaurants.name')
            ->orderByDesc('total_payments')
            ->limit(5)
            ->get();
    }

    private function getSubscriptionStats()
    {
        $this->subscriptionStats = [
            'total_subscriptions' => GlobalSubscription::count(),
            'active_subscriptions' => GlobalSubscription::where('subscription_status', 'active')->count(),
            'expired_subscriptions' => GlobalSubscription::where('subscription_status', 'inactive')->count(),
            'trial_subscriptions' => 0, // No trial status in current system
        ];
    }

    public function render()
    {
        return view('livewire.dashboard.superadmin-revenue-chart');
    }
}
