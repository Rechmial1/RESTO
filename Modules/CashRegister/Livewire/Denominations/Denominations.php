<?php

declare(strict_types=1);

namespace Modules\CashRegister\Livewire\Denominations;

use Modules\CashRegister\Entities\Denomination;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

final class Denominations extends Component
{
    use LivewireAlert;

    public $search = '';
    public $showCreateModal = false;

    public function openCreateModal()
    {
        $this->showCreateModal = true;
        $this->dispatch('openCreateModal');
    }
    
    #[On('denominationCreated')]
    #[On('denominationUpdated')]
    public function handleDenominationSaved()
    {
        $this->showCreateModal = false;
    }

    /**
     * Render the denominations management page
     */
    public function render()
    {
        return view('cashregister::livewire.denominations.denominations');
    }
}
