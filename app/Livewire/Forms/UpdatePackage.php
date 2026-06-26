<?php

namespace App\Livewire\Forms;

use App\Models\Package;
use Livewire\Component;
use App\Enums\PackageType;
use App\Models\Restaurant;
use App\Models\GlobalInvoice;
use App\Models\GlobalSubscription;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class UpdatePackage extends Component
{
    use LivewireAlert;

    public $restaurant;
    public $latestSubscription;
    public $packages;
    public $payDate;
    public $nextPayDate;
    public $licenceExpireOn;
    public $packageId;
    public $trialExpireOn;
    public $amount;
    public $allPackages;
    public $packageType;
    public $currentPackage;
    public $packageMonthlyStatus;
    public $packageAnnualStatus;
    public $monthlyPrice;
    public $billingCycle;
    public $annualPrice;
    public $selectedPackage;
    public $lifetimePrice;

    public function mount($updateRestaurantPackageId)
    {
        $this->restaurant = Restaurant::with(['package', 'currentInvoice', 'currency'])
            ->findOrFail($updateRestaurantPackageId);

        $this->latestSubscription = $this->restaurant;
        $this->packages = Package::all();
        $this->packageId = $this->restaurant->package_id;
        $this->initializeSelectedPackage();
    }

    public function updatedPackageId()
    {
        $this->initializeSelectedPackage();
    }

    public function updatedBillingCycle($value)
    {
        $this->updateAmountAndDates($value);
    }

    private function initializeSelectedPackage()
    {
        $this->selectedPackage = Package::findOrFail($this->packageId);
        $this->setPackageDetails();
        $this->setBillingCycleAndAmount();
    }

    private function setPackageDetails()
    {
        $this->packageType = $this->selectedPackage->package_type;
        $this->packageMonthlyStatus = $this->selectedPackage->monthly_status;
        $this->packageAnnualStatus = $this->selectedPackage->annual_status;
        $this->monthlyPrice = $this->selectedPackage->monthly_price;
        $this->annualPrice = $this->selectedPackage->annual_price;
        $this->lifetimePrice = $this->selectedPackage->price;
    }

    private function setBillingCycleAndAmount()
    {
        if ($this->selectedPackage->is_free) {
            if ($this->selectedPackage->package_type === PackageType::TRIAL) {
                $this->billingCycle = 'trial';
            } else {
                $this->billingCycle = 'free';
            }
            $this->amount = 0;
        } elseif ($this->selectedPackage->package_type === PackageType::LIFETIME) {
            $this->billingCycle = 'lifetime';
            $this->amount = $this->lifetimePrice;
        } else {
            $this->billingCycle = 'monthly';
            $this->amount = $this->monthlyPrice;
        }
        $this->updateAmountAndDates($this->billingCycle);
    }

    private function updateAmountAndDates($cycle)
    {
        $this->amount = $this->getAmountBasedOnBillingCycle($cycle);
        $this->setPaymentDates($cycle);
    }

    private function getAmountBasedOnBillingCycle($cycle)
    {
        return match ($cycle) {
            'monthly' => $this->monthlyPrice,
            'annual' => $this->annualPrice,
            'free' => 0,
            'lifetime' => $this->lifetimePrice,
            default => $this->monthlyPrice,
        };
    }

    private function setPaymentDates($cycle)
    {
        $this->payDate = now()->format('Y-m-d');

        $this->nextPayDate = match ($cycle) {
            'monthly' => now()->addMonth()->format('Y-m-d'),
            'annual' => now()->addYear()->format('Y-m-d'),
            'trial' => null,
            'free' => null,
            'lifetime' => null,
            default => null,
        };

        $this->trialExpireOn = now()->addDays($this->selectedPackage->trial_days ?? 0)->format('Y-m-d');

        // Set license expiration to match next pay date for recurring packages
        $this->licenceExpireOn = $this->nextPayDate;
    }

    public function updatePackageSubmit()
    {

        $this->validatePackageData();

        $restaurant = Restaurant::with('package')->findOrFail($this->restaurant->id);
        $package = Package::findOrFail($this->selectedPackage->id);
        $isTrial = $this->packageType === PackageType::TRIAL;
        $billingCycle = $this->billingCycle;

        try {
            // Update the restaurant with the new package details
            $restaurant->package_id = $package->id;
            $restaurant->package_type = $billingCycle;
            $restaurant->trial_ends_at = $isTrial && $this->trialExpireOn ? \Carbon\Carbon::parse($this->trialExpireOn)->format('Y-m-d') : null;
            $restaurant->is_active = true;
            $restaurant->status = 'active';
            $currencyId = $package->currency_id ?: global_setting()->currency_id;
            $payDate = $this->payDate ? \Carbon\Carbon::parse($this->payDate) : now();


            if ($isTrial && $this->trialExpireOn) {
                $restaurant->license_expire_on = \Carbon\Carbon::parse($this->trialExpireOn);
            } elseif ($billingCycle === 'lifetime') {
                $restaurant->license_expire_on = null;
            } elseif ($this->licenceExpireOn) {
                $restaurant->license_expire_on = \Carbon\Carbon::parse($this->licenceExpireOn);
            } else {
                $restaurant->license_expire_on = match ($billingCycle) {
                    'monthly' => $payDate->copy()->addMonth(),
                    'annual' => $payDate->copy()->addYear(),
                    'free' => null,
                    default => $payDate->copy()->addMonth(),
                };
            }

            // Deactivate existing subscriptions
            GlobalSubscription::where('restaurant_id', $restaurant->id)
                ->where('subscription_status', 'active')
                ->update(['subscription_status' => 'inactive']);


            // Create new subscription
            $subscription = GlobalSubscription::create([
                'restaurant_id' => $restaurant->id,
                'package_id' => $package->id,
                'currency_id' => $currencyId,
                'package_type' => $this->billingCycle,
                'quantity' => 1,
                'gateway_name' => 'offline',
                'subscription_status' => 'active',
                'subscribed_on_date' => $payDate,
                'ends_at' => $restaurant->license_expire_on,
                'transaction_id' => strtoupper(str()->random(15)),
            ]);

            // Parse next pay date properly
            $nextPayDate = null;
            if ($this->nextPayDate) {
                try {
                    $nextPayDate = \Carbon\Carbon::parse($this->nextPayDate);
                } catch (\Exception $e) {
                    $nextPayDate = null;
                }
            }

            // Create invoice for the subscription
            GlobalInvoice::create([
                'global_subscription_id' => $subscription->id,
                'restaurant_id' => $restaurant->id,
                'currency_id' => $currencyId,
                'package_id' => $restaurant->package_id,
                'package_type' => $subscription->package_type,
                'total' => $this->amount ?? 0,
                'gateway_name' => 'offline',
                'transaction_id' => $subscription->transaction_id,
                'pay_date' => $subscription->subscribed_on_date,
                'next_pay_date' => $nextPayDate,
            ]);

            $restaurant->save();

            cache()->forget('restaurant_' . $restaurant->id . '_staff_stats');
            cache()->forget('restaurant_' . $restaurant->id . '_menu_item_stats');
            // Clear order stats cache for all branches of this restaurant
            $restaurant->branches->each(function ($branch) {
                cache()->forget('branch_' . $branch->id . '_order_stats');
            });

            // Notify the user
            $this->dispatch('hideChangePackage');
            $this->alert('success', __('messages.packageUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close'),
            ]);
        } catch (\Throwable $th) {
            info($th);
            $this->alert('error', __('messages.somethingWentWrong') . ': ' . $th->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'timer' => 5000,
            ]);
        }
    }

    public function validatePackageData()
    {
        $rules = [
            'packageId' => 'required|exists:packages,id',
            'billingCycle' => 'required|in:monthly,annual,lifetime,free,trial',
        ];

        if ($this->billingCycle === 'trial') {
            $rules['trialExpireOn'] = 'required|date|after:today';
        } else {
            // For non-trial packages
            if ($this->billingCycle !== 'lifetime' && $this->billingCycle !== 'free') {
                $rules['amount'] = 'required|numeric|min:0';
                $rules['payDate'] = 'required|date';
                $rules['nextPayDate'] = 'required|date|after:payDate';
                $rules['licenceExpireOn'] = 'required|date';
            } elseif ($this->billingCycle === 'lifetime') {
                $rules['amount'] = 'required|numeric|min:0';
                $rules['payDate'] = 'required|date';
                // License and next pay date not required for lifetime
            } else {
                // Free package
                $rules['amount'] = 'nullable|numeric';
            }
        }

        $messages = [
            'packageId.required' => __('messages.packageRequired'),
            'packageId.exists' => __('messages.packageNotFound'),
            'billingCycle.required' => __('messages.billingCycleRequired'),
            'billingCycle.in' => __('messages.invalidBillingCycle'),
            'licenceExpireOn.required' => __('messages.licenceExpireRequired'),
            'licenceExpireOn.date' => __('messages.invalidDateFormat'),
            'trialExpireOn.required' => __('messages.trialExpireOnRequired'),
            'trialExpireOn.date' => __('messages.invalidDateFormat'),
            'trialExpireOn.after' => __('messages.trialExpireMustBeInFuture'),
            'amount.required' => __('messages.amountRequired'),
            'amount.numeric' => __('messages.amountNumeric'),
            'amount.min' => __('messages.amountMustBePositive'),
            'payDate.required' => __('messages.payDateRequired'),
            'payDate.date' => __('messages.invalidDateFormat'),
            'nextPayDate.required' => __('messages.nextPayDateRequired'),
            'nextPayDate.date' => __('messages.invalidDateFormat'),
            'nextPayDate.after' => __('messages.nextPayDateMustBeAfterPayDate'),
        ];

        $this->validate($rules, $messages);
    }


    public function render()
    {
        return view('livewire.forms.update-package');
    }
}
