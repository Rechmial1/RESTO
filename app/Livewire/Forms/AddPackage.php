<?php

namespace App\Livewire\Forms;

use App\Models\Module;
use App\Models\Package;
use Livewire\Component;
use App\Enums\PackageType;
use App\Models\GlobalCurrency;
use Illuminate\Validation\Rule;
use App\Models\SuperadminPaymentGateway;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class AddPackage extends Component
{
    use LivewireAlert;

    public $packageName;
    public $description;
    public $currencyID;
    public $price;
    public $annualPrice;
    public $monthlyPrice;
    public bool $isFree = false;
    public bool $monthlyStatus = false;
    public bool $annualStatus = false;
    public $sortOrder;
    public bool $isPrivate = false;
    public bool $isRecommended = false;
    public $packageType;
    public $trialDays;
    public $trialStatus;
    public $packageTypes;
    public bool $toggleSelectedModules = false;
    public $selectedModules = [];
    public $modules;
    public $currencies;
    public $currencySymbol;
    public $maxOrder;
    public $paymentKey;
    public $stripeAnnualPlanId;
    public $stripeMonthlyPlanId;
    public $razorpayAnnualPlanId;
    public $razorpayMonthlyPlanId;
    public $additionalFeatures;
    public $selectedFeatures = [];
    public $showPackageDetailsForm = true;
    public $showModulesForm = false;
    public $branchLimit;
    public $flutterwaveAnnualPlanId;
    public $flutterwaveMonthlyPlanId;
    public $paystackAnnualPlanId;
    public $paystackMonthlyPlanId;
    public $menuItemsLimit;
    public $orderLimit;
    public $staffLimit;
    public $smsCount = -1;
    public bool $carryForwardSms = false;
    public $paddleAnnualPriceId;
    public $paddleMonthlyPriceId;
    public $paddleLifetimePriceId;

    public function mount()
    {
        $this->maxOrder = Package::count();
        $this->sortOrder = $this->maxOrder + 1;
        $this->packageTypes = array_filter(PackageType::cases(), function ($type) {
            return !in_array($type, [PackageType::TRIAL, PackageType::DEFAULT, PackageType::FREE]);
        });

        // Load modules with SMS module filtering
        $this->modules = $this->getAvailableModules();

        $this->currencies = GlobalCurrency::all();
        $this->currencyID = global_setting()->default_currency_id;
        $defaultCurrency = $this->currencies->firstWhere('id', global_setting()->default_currency_id)
            ?? $this->currencies->first();
        $this->currencySymbol = $defaultCurrency->currency_symbol ?? null;
        $this->packageType = PackageType::STANDARD->value;
        $this->paymentKey = SuperadminPaymentGateway::first();
        $this->additionalFeatures = Package::ADDITIONAL_FEATURES;

        // Set default values for limit fields
        $this->menuItemsLimit = -1;
        $this->orderLimit = -1;
        $this->staffLimit = -1;
    }

    /**
     * Get available modules, filtering out disabled modules
     */
    private function getAvailableModules()
    {
        return Module::all()
            ->filter(fn ($module) => $module->name !== 'Sms' || module_enabled('Sms'));
    }

    public function isSmsModuleSelected()
    {
        $smsModule = Module::where('name', 'Sms')->first();
        return $smsModule && in_array($smsModule->id, $this->selectedModules);
    }

    public function isMenuItemModuleSelected()
    {
        $menuItemModule = Module::where('name', 'Menu Item')->first();
        return $menuItemModule && in_array($menuItemModule->id, $this->selectedModules);
    }

    public function isOrderModuleSelected()
    {
        $orderModule = Module::where('name', 'Order')->first();
        return $orderModule && in_array($orderModule->id, $this->selectedModules);
    }

    public function isStaffModuleSelected()
    {
        $staffModule = Module::where('name', 'Staff')->first();
        return $staffModule && in_array($staffModule->id, $this->selectedModules);
    }

    public function updatedSelectedModules()
    {
        // Reset SMS count to default when SMS module is deselected
        if (!$this->isSmsModuleSelected()) {
            $this->smsCount = -1; // Default to -1 (unlimited)
            $this->carryForwardSms = false; // Reset carry forward SMS
        }

        // Reset menu items limit when Menu Item module is deselected
        if (!$this->isMenuItemModuleSelected()) {
            $this->menuItemsLimit = -1;
        }

        // Reset order limit when Order module is deselected
        if (!$this->isOrderModuleSelected()) {
            $this->orderLimit = -1;
        }

        // Reset staff limit when Staff module is deselected
        if (!$this->isStaffModuleSelected()) {
            $this->staffLimit = -1;
        }
    }

    public function updatedCurrencyID()
    {
        $this->currencySymbol = GlobalCurrency::find($this->currencyID)->currency_symbol ?? null;
    }

    public function goBack()
    {
        $this->showPackageDetailsForm = true;
        $this->showModulesForm = false;
    }

    public function updatedPackageType($value)
    {
        if ($value == PackageType::LIFETIME->value) {
            $this->annualStatus = false;
            $this->monthlyStatus = false;
            $this->annualPrice = null;
            $this->monthlyPrice = null;
        }
    }

    public function updatedIsFree($value)
    {
        if ($value) {
            $this->packageType = PackageType::FREE->value;
            $this->annualStatus = false;
            $this->monthlyStatus = false;
            $this->annualPrice = null;
            $this->monthlyPrice = null;
        }
    }

    public function updatedToggleSelectedModules($value)
    {
        $this->selectedModules = $value ? $this->modules->pluck('id')->toArray() : [];
    }

    public function submitForm()
    {
        $validateRules = [
            'packageName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('packages', 'package_name'),
            ],
            'isFree' => 'required|boolean',
            'sortOrder' => 'required|integer',
            'isPrivate' => 'required|boolean',
            'isRecommended' => 'required|boolean',
            'packageType' => 'required_if:isFree,false',
            'currencyID' => 'required_if:isFree,false',
            'annualStatus' => 'required_if:packageType,standard|boolean',
            'monthlyStatus' => 'required_if:packageType,standard|boolean',
            'price' => 'required_if:packageType,lifetime|numeric|nullable',
            'annualPrice' => [
                'nullable',
                'numeric',
                'required_if:annualStatus,true',
            ],
            'monthlyPrice' => [
                'nullable',
                'numeric',
                'required_if:monthlyStatus,true',
            ],
            'description' => 'required',
            'selectedModules' => 'array|min:1',
            'branchLimit' => [
                'nullable',
                'integer',
                'min:-1',
                Rule::requiredIf(fn() => in_array('Change Branch', $this->selectedFeatures))
            ],
            'menuItemsLimit' => [
                Rule::requiredIf(fn() => $this->isMenuItemModuleSelected()),
                'nullable',
                'integer',
                'min:-1',
            ],
            'orderLimit' => [
                Rule::requiredIf(fn() => $this->isOrderModuleSelected()),
                'nullable',
                'integer',
                'min:-1',
            ],
            'staffLimit' => [
                Rule::requiredIf(fn() => $this->isStaffModuleSelected()),
                'nullable',
                'integer',
                'min:-1',
            ],
            'smsCount' => [
                Rule::requiredIf(fn() => $this->isSmsModuleSelected()),
                'nullable',
                'integer',
                'min:-1',
            ],
            'carryForwardSms' => 'boolean',
        ];

        if (($this->monthlyPrice == true ) && ($this->paymentKey->razorpay_status == 1)) {
            $validateRules['razorpayMonthlyPlanId'] = 'required';
        }

        if (($this->annualPrice == true ) && ($this->paymentKey->razorpay_status == 1)) {
            $validateRules['razorpayAnnualPlanId'] = 'required';
        }

        if (($this->annualPrice == true ) && ($this->paymentKey->stripe_status == 1)) {
            $validateRules['stripeAnnualPlanId'] = 'required';
        }

        if (($this->monthlyPrice == true ) && ($this->paymentKey->stripe_status == 1)) {
            $validateRules['stripeMonthlyPlanId'] = 'required';
        }

        if (($this->monthlyPrice == true ) && ($this->paymentKey->flutterwave_status == 1)) {
            $validateRules['flutterwaveMonthlyPlanId'] = 'required';
        }

        if (($this->annualPrice == true ) && ($this->paymentKey->flutterwave_status == 1)) {
            $validateRules['flutterwaveAnnualPlanId'] = 'required';
        }

        if (($this->monthlyPrice == true ) && ($this->paymentKey->paystack_status == 1)) {
            $validateRules['paystackMonthlyPlanId'] = 'required';
        }

        if (($this->annualPrice == true ) && ($this->paymentKey->paystack_status == 1)) {
            $validateRules['paystackAnnualPlanId'] = 'required';
        }

        if (($this->monthlyPrice == true ) && ($this->paymentKey->paddle_status == 1)) {
            $validateRules['paddleMonthlyPriceId'] = 'required';
        }

        if (($this->annualPrice == true ) && ($this->paymentKey->paddle_status == 1)) {
            $validateRules['paddleAnnualPriceId'] = 'required';
        }

        if (($this->packageType == 'lifetime') && ($this->paymentKey->paddle_status == 1)) {
            $validateRules['paddleLifetimePriceId'] = 'required';
        }

        $validateMessages = [
            'selectedModules.min' => 'Please select at least one module',
            'packageName.unique' => 'The package name has already been taken.',
            'price.required_if' => 'The price field is required.',
            'annualPrice.required_if' => 'The annual price field is required.',
            'monthlyPrice.required_if' => 'The monthly price field is required.',
            'branchLimit.required_if' => 'The branch limit field is required when Change Branch is selected.',
            'menuItemsLimit.required_if' => 'Menu items limit is required when Menu Item module is selected.',
            'menuItemsLimit.min' => 'Menu items limit must be -1 or greater.',
            'orderLimit.required_if' => 'Order limit is required when Order module is selected.',
            'orderLimit.min' => 'Order limit must be -1 or greater.',
            'staffLimit.required_if' => 'Staff limit is required when Staff module is selected.',
            'staffLimit.min' => 'Staff limit must be -1 or greater.',
            'smsCount.required_if' => 'SMS count is required when SMS module is enabled.',
            'smsCount.min' => 'SMS count must be at least -1 (use -1 for unlimited).',
        ];

        $this->validate($validateRules, $validateMessages);


        Package::where('sort_order', '>=', $this->sortOrder)
            ->increment('sort_order');

        $package = new Package();
        $package->package_name = $this->packageName;
        $package->description = $this->description;
        $package->package_type = $this->packageType;
        $package->price = $this->packageType === PackageType::LIFETIME->value ? $this->price : 0;
        $package->currency_id = $this->currencyID;
        $package->annual_price = $this->annualPrice;
        $package->monthly_price = $this->monthlyPrice;
        $package->is_free = $this->isFree;
        $package->monthly_status = $this->monthlyStatus;
        $package->annual_status = $this->annualStatus;
        $package->sort_order = $this->sortOrder;
        $package->is_private = $this->isPrivate;
        $package->is_recommended = $this->isRecommended;
        $package->stripe_annual_plan_id = $this->stripeAnnualPlanId;
        $package->stripe_monthly_plan_id = $this->stripeMonthlyPlanId;
        $package->razorpay_annual_plan_id = $this->razorpayAnnualPlanId;
        $package->razorpay_monthly_plan_id = $this->razorpayMonthlyPlanId;
        $package->flutterwave_annual_plan_id = $this->flutterwaveAnnualPlanId;
        $package->flutterwave_monthly_plan_id = $this->flutterwaveMonthlyPlanId;
        $package->paystack_annual_plan_id = $this->paystackAnnualPlanId;
        $package->paystack_monthly_plan_id = $this->paystackMonthlyPlanId;
        $package->paddle_annual_price_id = $this->paddleAnnualPriceId;
        $package->paddle_monthly_price_id = $this->paddleMonthlyPriceId;
        $package->paddle_lifetime_price_id = $this->paddleLifetimePriceId;
        $package->additional_features = json_encode($this->selectedFeatures);
        $package->branch_limit = $this->branchLimit;
        $package->menu_items_limit = $this->menuItemsLimit;
        $package->order_limit = $this->orderLimit;
        $package->staff_limit = $this->staffLimit;
        $package->sms_count = $this->smsCount ?? -1;
        $package->carry_forward_sms = $this->carryForwardSms;
        $package->save();

        $package->modules()->sync($this->selectedModules);

        $this->dispatch('hideAddPackage');

        $this->alert('success', __('messages.packageAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);

        return $this->redirect(route('superadmin.packages.index'), navigate: true);
    }


    public function render()
    {
        return view('livewire.forms.add-package');
    }
}
