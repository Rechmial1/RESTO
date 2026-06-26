<?php

namespace App\Livewire;

use App\Models\Country;
use App\Models\Customer;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class UpdateProfile extends Component
{
    use LivewireAlert;

    public $fullName;
    public $email;
    public $phone;
    public $phoneCode;
    public $address;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;

    public function mount()
    {
        if (is_null(customer()))
        {
            return $this->redirect(route('home'));
        }
        
        $this->fullName = customer()->name;
        $this->email = customer()->email;
        $this->phone = customer()->phone;
        $this->phoneCode = customer()->phone_code;
        $this->address = customer()->delivery_address;

        // Initialize phone codes
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
    }

    public function updatedPhoneCodeIsOpen($value)
    {
        if (!$value) {
            $this->reset(['phoneCodeSearch']);
            $this->updatedPhoneCodeSearch();
        }
    }

    public function updatedPhoneCodeSearch()
    {
        $this->filteredPhoneCodes = $this->allPhoneCodes->filter(function ($phonecode) {
            return str_contains($phonecode, $this->phoneCodeSearch);
        })->values();
    }

    public function selectPhoneCode($phonecode)
    {
        $this->phoneCode = $phonecode;
        $this->phoneCodeIsOpen = false;
        $this->phoneCodeSearch = '';
        $this->updatedPhoneCodeSearch();
    }

    public function submitForm()
    {
        $this->validate([
            'fullName' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ]);

        $customer = Customer::findOrFail(customer()->id);
        $customer->name = $this->fullName;
        $customer->phone = $this->phone;
        $customer->phone_code = $this->phoneCode;
        $customer->delivery_address = $this->address;
        $customer->save();

        session(['customer' => $customer]);
        $this->dispatch('setCustomer', customer: $customer);

        $this->alert('success', __('messages.profileUpdated'), [
            'position' => 'center'
        ]);

    }

    public function render()
    {
        return view('livewire.update-profile', [
            'phonecodes' => $this->filteredPhoneCodes,
        ]);
    }

}
