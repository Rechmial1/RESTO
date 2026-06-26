<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\Country;
use Livewire\Component;
use App\Models\Restaurant;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Carbon\Carbon;

class EditRestaurant extends Component
{

    use LivewireAlert;

    public $restaurantName;
    public $fullName;
    public $email;
    public $phone;
    public $phoneCode;
    public $address;
    public $country;
    public $facebook;
    public $instagram;
    public $twitter;
    public $countries;
    public $restaurant;
    public $isActive;
    public $sub_domain;
    public $domain;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;
    public $association_code;
    public $is_primary = 0;
    public $primary_restaurant_id;
    public $availablePrimaryRestaurants;
    
    // NOUVEAUX CHAMPS EMECEf
    public $reg_com;
    public $ifu;
    public $api_jeton;
    public $sfe_status = 0;
    public $sfe_activated_at;
    public $showApiToken = false;

    public function mount($restaurantId)
    {
        $this->restaurant = Restaurant::findOrFail($restaurantId);
        $this->initializeFormData();
    }

    public function initializeFormData()
    {
        if (module_enabled('Subdomain')) {
            $this->sub_domain = str_replace('.' . getDomain(), '', $this->restaurant->sub_domain);
            $this->domain = str($this->restaurant->sub_domain)->endsWith(getDomain()) ? '.' . getDomain() : '';
        }

        $ipCountry = (new User)->getCountryFromIp();

        $defaultCountry = Country::where('countries_code', $ipCountry)->first();

        $this->countries = Country::select('id', 'countries_name')->get();

        $this->restaurantName = $this->restaurant->name;
        $this->email = $this->restaurant->email;
        $this->phone = $this->restaurant->phone_number;
        $this->phoneCode = $this->restaurant->phone_code;
        $this->address = $this->restaurant->address;
        $this->country = $this->restaurant->country_id;
        $this->facebook = $this->restaurant->facebook_link;
        $this->instagram = $this->restaurant->instagram_link;
        $this->twitter = $this->restaurant->twitter_link;
        $this->isActive = (int)$this->restaurant->is_active;
        $this->association_code = $this->restaurant->association_code;
        $this->is_primary = (int)$this->restaurant->is_primary;
        $this->primary_restaurant_id = $this->restaurant->primary_restaurant_id;
        $this->availablePrimaryRestaurants = Restaurant::withoutGlobalScopes()
            ->where('id', '!=', $this->restaurant->id)
            ->where('is_primary', true)
            ->orderBy('name')
            ->get(['id', 'name', 'association_code']);
        
        // NOUVEAUX CHAMPS EMECEf - Initialisation
        $this->reg_com = $this->restaurant->reg_com;
        $this->ifu = $this->restaurant->ifu;
        $this->api_jeton = $this->restaurant->api_jeton;
        $this->sfe_status = (int)($this->restaurant->sfe_status ?? 0);
        $this->sfe_activated_at = $this->restaurant->sfe_activated_at;

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
    
    // NOUVELLE METHODE - Basculer la visibilité du token API
    public function toggleApiTokenVisibility()
    {
        $this->showApiToken = !$this->showApiToken;
    }
    
    // NOUVELLE METHODE - Vérifier le format IFU Bénin (13 chiffres)
    public function validateIfuFormat()
    {
        if (!empty($this->ifu) && !preg_match('/^[0-9]{13}$/', $this->ifu)) {
            $this->addError('ifu', 'L\'IFU doit être composé exactement de 13 chiffres');
            return false;
        }
        return true;
    }

    public function submitForm()
    {
        $rules = [
            'restaurantName' => 'required',
            'email' => 'required|email',
            'isActive' => 'required|in:0,1',
            'association_code' => 'required|string|size:5|alpha_num|unique:restaurants,association_code,' . $this->restaurant->id,
            'is_primary' => 'required|in:0,1',
            'primary_restaurant_id' => 'nullable|exists:restaurants,id',
            
            // NOUVELLES RÈGLES DE VALIDATION EMECEf
            'reg_com' => 'nullable|string|max:191',
            'ifu' => 'nullable|string|max:191|regex:/^[0-9]{13}$/', // Format IFU Bénin: 13 chiffres
            'api_jeton' => 'nullable|string',
            'sfe_status' => 'boolean',
        ];

        if (module_enabled('Subdomain')) {
            // Validate domain or subdomain based on input
            if (empty($this->domain)) {
                $rules['sub_domain'] = 'required|string';
            } else {
                $rules['sub_domain'] = 'required|min:3|max:50|regex:/^[a-z0-9\-_]{2,20}$/|banned_sub_domain';
            }
        }

        // Validation personnalisée pour l'IFU
        $this->validateIfuFormat();

        $this->validate($rules);

        if (module_enabled('Subdomain')) {
            $restaurant = Restaurant::where('id', '!=', $this->restaurant->id)
                ->where('sub_domain', strtolower($this->sub_domain . $this->domain))
                ->exists();

            if ($restaurant) {
                $this->addError('sub_domain', __('subdomain::app.messages.subdomainAlreadyExists'));
                return;
            }

            $this->restaurant->sub_domain = strtolower($this->sub_domain . $this->domain);
        }

        if ((int)$this->is_primary === 1 && (int)$this->primary_restaurant_id > 0) {
            $this->addError('primary_restaurant_id', 'Une entreprise primaire ne peut pas etre secondaire.');
            return;
        }

        // Vérifier si le statut EMECEf change de 0 à 1 pour enregistrer la date d'activation
        $oldSfeStatus = (int)$this->restaurant->sfe_status;
        $newSfeStatus = (int)$this->sfe_status;
        
        if ($oldSfeStatus === 0 && $newSfeStatus === 1) {
            $this->restaurant->sfe_activated_at = Carbon::now();
        } elseif ($newSfeStatus === 0) {
            // Si on désactive, on efface la date d'activation
            $this->restaurant->sfe_activated_at = null;
        }

        $this->restaurant->name = $this->restaurantName;
        $this->restaurant->address = $this->address;
        $this->restaurant->email = $this->email;
        $this->restaurant->phone_number = $this->phone;
        $this->restaurant->phone_code = $this->phoneCode;
        $this->restaurant->country_id = $this->country;
        $this->restaurant->facebook_link = $this->facebook;
        $this->restaurant->instagram_link = $this->instagram;
        $this->restaurant->twitter_link = $this->twitter;
        $this->restaurant->is_active = (bool)$this->isActive;
        $this->restaurant->association_code = strtoupper($this->association_code);
        $this->restaurant->is_primary = (bool)$this->is_primary;
        $this->restaurant->primary_restaurant_id = (int)$this->is_primary === 1 ? null : $this->primary_restaurant_id;
        
        // NOUVEAUX CHAMPS EMECEf - Sauvegarde
        $this->restaurant->reg_com = $this->reg_com;
        $this->restaurant->ifu = $this->ifu;
        $this->restaurant->api_jeton = $this->api_jeton;
        $this->restaurant->sfe_status = (bool)$this->sfe_status;
        
        $this->restaurant->save();

        $this->dispatch('hideEditStaff');

        // Message de succès avec information sur l'activation EMECEf
        $message = __('messages.restaurantUpdated');
        if ($newSfeStatus === 1 && $oldSfeStatus === 0) {
            $message .= ' et activation EMECEf réussie.';
        }

        $this->alert('success', $message, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function render()
    {
        return view('livewire.forms.edit-restaurant', [
            'phonecodes' => $this->filteredPhoneCodes,
        ]);
    }

}
