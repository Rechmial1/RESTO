<?php

namespace App\Livewire\Forms;

use App\Helper\Common;
use App\Models\Country;
use App\Models\Customer;
use App\Models\Reservation;
use App\Models\ReservationSetting;
use Carbon\Carbon;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use App\Events\TodayReservationCreatedEvent;

class NewReservation extends Component
{

    use LivewireAlert;

    public $reservationSettings;
    public $date;
    public $period;
    public $numberOfGuests;
    public $slotType;
    public $specialRequest;
    public $availableTimeSlots = [];
    public $customerName;
    public $customerPhone;
    public $customerEmail;
    public $phoneCode;
    public $phoneCodeSearch = '';
    public $phoneCodeIsOpen = false;
    public $allPhoneCodes;
    public $filteredPhoneCodes;

    // Customer search properties
    public $searchQuery = '';
    public $customerSearchResults = [];
    public $selectedCustomerId = null;

    // Time slot options (empty until date and slot type are selected)
    public $timeSlots = [];

    public function mount()
    {
        $this->date = now(timezone())->format('Y-m-d');
        $this->slotType = 'Lunch';
        $this->numberOfGuests = 1;
        $this->loadAvailableTimeSlots();

        // Initialize phone codes
        $this->allPhoneCodes = collect(Country::pluck('phonecode')->unique()->filter()->values());
        $this->filteredPhoneCodes = $this->allPhoneCodes;
        $this->phoneCode = restaurant()->phone_code ?? $this->allPhoneCodes->first();
    }

    // Customer search methods
    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) >= 2) {
            $this->customerSearchResults = $this->fetchCustomerResults();
        } else {
            $this->customerSearchResults = [];
        }
    }

    public function fetchCustomerResults()
    {
        if (empty($this->searchQuery)) {
            return collect();
        }

        $safeTerm = Common::safeString($this->searchQuery);

        return Customer::where('restaurant_id', restaurant()->id)
            ->where(function ($query) use ($safeTerm) {
                $query->where('name', 'like', '%' . $safeTerm . '%')
                    ->orWhere('phone', 'like', '%' . $safeTerm . '%')
                    ->orWhere('email', 'like', '%' . $safeTerm . '%');
            })
            ->orderBy('name')
            ->limit(10)
            ->get();
    }

    public function selectCustomer($customerId)
    {
        $customer = Customer::find($customerId);

        if ($customer) {
            $this->selectedCustomerId = $customer->id;
            $this->customerName = $customer->name;
            $this->customerPhone = $customer->phone;
            $this->phoneCode = $customer->phone_code ?? restaurant()->phone_code ?? $this->allPhoneCodes->first();
            $this->customerEmail = $customer->email;
            $this->searchQuery = '';
            $this->customerSearchResults = [];
        }
    }

    public function clearCustomerSelection()
    {
        $this->selectedCustomerId = null;
        $this->customerName = '';
        $this->customerPhone = '';
        $this->customerEmail = '';
        $this->phoneCode = restaurant()->phone_code ?? $this->allPhoneCodes->first();
        $this->searchQuery = '';
        $this->customerSearchResults = [];
    }

    public function resetCustomerSearch()
    {
        $this->customerSearchResults = [];
        $this->searchQuery = '';
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

    public function updatedDate()
    {
        $this->loadAvailableTimeSlots();
    }

    public function setReservationGuest($noOfGuests)
    {
        // Get minimum party size from restaurant settings
        $minimumPartySize = restaurant() ? (restaurant()->minimum_party_size ?? 1) : 1;

        // Validate that the selected number of guests meets the minimum requirement
        if ($noOfGuests < $minimumPartySize) {
            $this->addError('numberOfGuests', __('messages.minimumPartySizeRequired', ['size' => $minimumPartySize]));
            return;
        }

        $this->numberOfGuests = $noOfGuests;
    }

    public function setReservationSlotType($type)
    {
        $this->slotType = $type;
        $this->loadAvailableTimeSlots();
    }

    public function updatedSlotType()
    {
        $this->loadAvailableTimeSlots();
    }

    public function loadAvailableTimeSlots()
    {
        $this->timeSlots = [];

        if ($this->date && $this->slotType) {
            // Ensure we're using the correct date format
            $parsedDate = Carbon::parse($this->date);
            $dayOfWeek = $parsedDate->format('l');
            $selectedDate = $parsedDate->format('Y-m-d');
            $currentTimezone = timezone();

            $now = Carbon::now($currentTimezone);
            $restaurant = restaurant();
            $disableSlotMinutes = $restaurant ? (int)($restaurant->disable_slot_minutes ?? 30) : 30;
            $currentTimeWithBuffer = $now->copy()->addMinutes($disableSlotMinutes);

            $settings = ReservationSetting::where('day_of_week', $dayOfWeek)
                ->where('slot_type', $this->slotType)
                ->where('available', 1)
                ->first();

            if ($settings) {
                // Generate time slots based on the time slot difference
                $startTime = Carbon::parse($settings->time_slot_start);
                $endTime = Carbon::parse($settings->time_slot_end);
                $slotDifference = (int)$settings->time_slot_difference;

                while ($startTime->lte($endTime)) {
                    $slotTime = $startTime->format('H:i:s');
                    $slotDateTime = Carbon::parse("{$selectedDate} {$slotTime}", $currentTimezone);

                    // Check if this is today and if the slot should be disabled
                    $isToday = $selectedDate === $now->format('Y-m-d');
                    $isDisabled = false;

                    if ($isToday) {
                        // For today, check if the slot is within the buffer time
                        $isDisabled = $slotDateTime->lte($currentTimeWithBuffer);
                    }

                    $this->timeSlots[] = [
                        'time' => $slotTime,
                        'disabled' => $isDisabled
                    ];

                    $startTime->addMinutes($slotDifference);
                }
            }
        }
    }

    public function submitReservation()
    {
        // Get minimum party size from restaurant settings
        $minimumPartySize = restaurant()->minimum_party_size ?? 1;

        $this->validate([
            'availableTimeSlots' => 'required',
            'customerName' => 'required',
            'numberOfGuests' => "required|integer|min:{$minimumPartySize}",
        ]);

        if ($this->availableTimeSlots) {
            $selectedDate = Carbon::parse($this->date)->format('Y-m-d');
            $currentTimezone = timezone() ?: 'Asia/Kolkata';
            $now = Carbon::now($currentTimezone);
            $restaurant = restaurant();
            $disableSlotMinutes = $restaurant ? (int)($restaurant->disable_slot_minutes ?? 30) : 30;
            $currentTimeWithBuffer = $now->copy()->addMinutes($disableSlotMinutes);

            $slotDateTime = Carbon::parse("{$selectedDate} {$this->availableTimeSlots}", $currentTimezone);
            $isToday = $selectedDate === $now->format('Y-m-d');

            if ($isToday && $slotDateTime->lte($currentTimeWithBuffer)) {
                $this->addError('availableTimeSlots', __('messages.slotDisabled'));
                return;
            }
        }

        // If customer is already selected from search, use that
        if ($this->selectedCustomerId) {
            $customer = Customer::find($this->selectedCustomerId);
            // Update customer details if they were modified
            $customer->update([
                'name' => $this->customerName,
                'phone' => $this->customerPhone,
                'phone_code' => $this->phoneCode,
                'email' => $this->customerEmail
            ]);
        } else {
            // Check for existing customer by email or phone
            $existingCustomer = null;

            if (!empty($this->customerEmail)) {
                $existingCustomer = Customer::where('restaurant_id', restaurant()->id)
                    ->where('email', $this->customerEmail)
                    ->first();
            }

            if (!$existingCustomer && !empty($this->customerPhone)) {
                $existingCustomer = Customer::where('restaurant_id', restaurant()->id)
                    ->where('phone', $this->customerPhone)
                    ->first();
            }

            if ($existingCustomer) {
                $existingCustomer->update([
                    'name' => $this->customerName,
                    'phone' => $this->customerPhone,
                    'phone_code' => $this->phoneCode
                ]);
                $customer = $existingCustomer;
            } else {
                $customer = Customer::create([
                    'name' => $this->customerName,
                    'phone' => $this->customerPhone,
                    'phone_code' => $this->phoneCode,
                    'email' => $this->customerEmail,
                    'restaurant_id' => restaurant()->id
                ]);
            }
        }

        Reservation::create([
            'reservation_date_time' => $this->date . ' ' . $this->availableTimeSlots,
            'customer_id' => $customer->id,
            'party_size' => $this->numberOfGuests,
            'reservation_slot_type' => $this->slotType,
            'special_requests' => $this->specialRequest,
            'slot_time_difference' => ReservationSetting::where('slot_type', $this->slotType)->first()->time_slot_difference
        ]);

        $this->alert('success', __('messages.reservationConfirmed'), [
            'toast' => false,
            'position' => 'center',
            'showCancelButton' => true,
            'cancelButtonText' => __('app.close')
        ]);

        return $this->redirect(route('reservations.index'));
    }

    public function render()
    {
        return view('livewire.forms.new-reservation', [
            'phonecodes' => $this->filteredPhoneCodes,
            'searchResults' => $this->customerSearchResults,
        ]);
    }
}
