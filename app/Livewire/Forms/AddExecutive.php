<?php

namespace App\Livewire\Forms;

use App\Models\Country;
use App\Models\DeliveryExecutive;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class AddExecutive extends Component
{

    use LivewireAlert;

    public $memberName;
    public $memberPhone;
    public $status = 'available';
    public $phoneCode;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;

    public function mount()
    {
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
            'memberName' => 'required',
            'phoneCode' => 'required',
            'memberPhone' => [
                'required',
                'regex:/^[0-9\s]{8,20}$/',
                'unique:delivery_executives,phone'
            ]
        ]);

        DeliveryExecutive::create([
            'name' => $this->memberName,
            'phone' => $this->memberPhone,
            'phone_code' => $this->phoneCode,
            'status' => $this->status,
        ]);

        // Reset the value
        $this->memberName = '';
        $this->memberPhone = '';
        $this->phoneCode = '';
        $this->status = 'available';

        $this->dispatch('hideAddStaff');

        $this->alert('success', __('messages.memberAdded'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.add-executive', [
            'phonecodes' => $this->filteredPhoneCodes,
        ]);
    }

}
