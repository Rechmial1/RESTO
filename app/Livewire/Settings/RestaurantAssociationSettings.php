<?php

namespace App\Livewire\Settings;

use App\Models\Restaurant;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class RestaurantAssociationSettings extends Component
{
    use LivewireAlert;

    public Restaurant $settings;
    public string $secondaryCode = '';

    public function mount(Restaurant $settings)
    {
        abort_unless(user()->hasRole('Admin_' . $settings->id), 403);

        $this->settings = $settings;
    }

    public function makePrimary()
    {
        $this->authorizePrimaryAdmin();

        $this->settings->is_primary = true;
        $this->settings->primary_restaurant_id = null;
        $this->settings->association_code ??= Restaurant::generateAssociationCode();
        $this->settings->save();

        session()->forget('restaurant');
        $this->settings = $this->settings->fresh(['secondaryRestaurants']);

        $this->success('Entreprise definie comme primaire.');
    }

    public function addSecondary()
    {
        $this->authorizePrimaryAdmin();

        if (!$this->settings->is_primary) {
            $this->addError('secondaryCode', 'Definissez d abord cette entreprise comme primaire.');
            return;
        }

        $this->validate([
            'secondaryCode' => 'required|string|size:5|alpha_num',
        ]);

        if ($this->settings->secondaryRestaurants()->count() >= 5) {
            $this->addError('secondaryCode', 'Vous pouvez associer au maximum 5 entreprises secondaires.');
            return;
        }

        $secondary = Restaurant::withoutGlobalScopes()
            ->where('association_code', strtoupper($this->secondaryCode))
            ->first();

        if (!$secondary) {
            $this->addError('secondaryCode', 'Aucune entreprise ne correspond a ce code.');
            return;
        }

        if ($secondary->id === $this->settings->id) {
            $this->addError('secondaryCode', 'Une entreprise ne peut pas etre associee a elle-meme.');
            return;
        }

        if ($secondary->is_primary) {
            $this->addError('secondaryCode', 'Une entreprise primaire ne peut pas etre ajoutee comme secondaire.');
            return;
        }

        if ($secondary->primary_restaurant_id && (int)$secondary->primary_restaurant_id !== (int)$this->settings->id) {
            $this->addError('secondaryCode', 'Cette entreprise est deja associee a une autre primaire.');
            return;
        }

        $secondary->primary_restaurant_id = $this->settings->id;
        $secondary->save();

        $this->secondaryCode = '';
        session()->forget('restaurant');
        $this->settings = $this->settings->fresh(['secondaryRestaurants']);

        $this->success('Entreprise secondaire ajoutee.');
    }

    public function removeSecondary($restaurantId)
    {
        $this->authorizePrimaryAdmin();

        $secondary = Restaurant::withoutGlobalScopes()
            ->where('primary_restaurant_id', $this->settings->id)
            ->findOrFail($restaurantId);

        $secondary->primary_restaurant_id = null;
        $secondary->save();

        session()->forget('restaurant');
        $this->settings = $this->settings->fresh(['secondaryRestaurants']);

        $this->success('Entreprise secondaire retiree.');
    }

    private function authorizePrimaryAdmin(): void
    {
        abort_unless(user()->hasRole('Admin_' . $this->settings->id), 403);
    }

    private function success(string $message): void
    {
        $this->alert('success', $message, [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close'),
        ]);
    }

    public function render()
    {
        return view('livewire.settings.restaurant-association-settings', [
            'secondaryRestaurants' => $this->settings->secondaryRestaurants()->orderBy('name')->get(),
            'primaryRestaurant' => $this->settings->primaryRestaurant,
        ]);
    }
}
