<?php

namespace App\Livewire\Forms;

use Livewire\Component;

class WifiButton extends Component
{
    public $showWifiModal = false;
    public $restaurant;

    public function mount($restaurant)
    {
        $this->restaurant = $restaurant;
    }

    public function openWifiModal()
    {
        $this->showWifiModal = true;
    }

    public function closeWifiModal()
    {
        $this->showWifiModal = false;
    }

    public function render()
    {
        return view('livewire.forms.wifi-button');
    }
}

