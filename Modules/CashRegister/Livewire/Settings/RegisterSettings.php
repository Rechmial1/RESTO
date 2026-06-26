<?php

namespace Modules\CashRegister\Livewire\Settings;

use Livewire\Component;
use Modules\CashRegister\Entities\CashRegisterSetting;
use App\Models\Role;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class RegisterSettings extends Component
{
    use LivewireAlert;

    public $forceOpenAfterLogin = false;
    public $selectedRoles = [];
    public $availableRoles = [];

    public function mount()
    {
        $this->loadSettings();
        $this->loadAvailableRoles();
    }

    public function loadSettings()
    {
        $settings = CashRegisterSetting::where('restaurant_id', auth()->user()->restaurant_id)->first();
        
        if ($settings) {
            $this->forceOpenAfterLogin = $settings->force_open_after_login;
            $this->selectedRoles = $settings->force_open_roles ?? [];
        }
    }

    public function loadAvailableRoles()
    {
        $this->availableRoles = Role::query()
            // Exclude only Super Admin from selection
            ->where('display_name', '<>', 'Super Admin')
            // Include roles belonging to this restaurant OR global roles (restaurant_id NULL)
            ->where(function ($q) {
                $q->where('restaurant_id', auth()->user()->restaurant_id)
                    ->orWhereNull('restaurant_id');
            })
            ->orderBy('display_name')
            ->get();
    }

    public function updatedForceOpenAfterLogin()
    {
        // This method will be called automatically when forceOpenAfterLogin changes
        // No need to do anything here, just ensures Livewire reactivity
    }

    public function save()
    {
        $this->validate([
            'forceOpenAfterLogin' => 'boolean',
            'selectedRoles' => 'array',
            'selectedRoles.*' => 'exists:roles,id',
        ]);

        $settings = CashRegisterSetting::updateOrCreate(
            ['restaurant_id' => auth()->user()->restaurant_id],
            [
                'force_open_after_login' => $this->forceOpenAfterLogin,
                'force_open_roles' => $this->selectedRoles,
            ]
        );

        $this->alert('success', 'Settings saved successfully!', [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('cashregister::livewire.settings.register-settings');
    }
}
