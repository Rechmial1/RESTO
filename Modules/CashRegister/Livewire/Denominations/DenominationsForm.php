<?php

declare(strict_types=1);

namespace Modules\CashRegister\Livewire\Denominations;

use App\Models\GlobalCurrency;
use Modules\CashRegister\Entities\Denomination;
use Illuminate\Support\Facades\Log;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;

final class DenominationsForm extends Component
{
    use LivewireAlert;

    // Form properties
    public ?Denomination $denomination = null;
    public string $name = '';
    public float $value = 0.00;
    public string $type = 'coin';
    // currency removed
    public string $description = '';
    public bool $is_active = true;

    // Modal state
    public bool $showModal = false;
    public bool $isEditMode = false;

    protected function rules(): array
    {
        $rules = Denomination::getCreateRules();
        
        if ($this->isEditMode && $this->denomination) {
            $rules = Denomination::getUpdateRules($this->denomination->id);
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'name.required' => __('cashregister::validation.denomination.name.required'),
            'name.max' => __('cashregister::validation.denomination.name.max'),
            'value.required' => __('cashregister::validation.denomination.value.required'),
            'value.numeric' => __('cashregister::validation.denomination.value.numeric'),
            'value.min' => __('cashregister::validation.denomination.value.min'),
            'value.max' => __('cashregister::validation.denomination.value.max'),
            'type.required' => __('cashregister::validation.denomination.type.required'),
            'type.in' => __('cashregister::validation.denomination.type.in'),
            // currency removed from validation
            'description.max' => __('cashregister::validation.denomination.description.max'),
        ];
    }

    public function mount(): void
    {
        $this->resetForm();
    }

    #[On('openCreateModal')]
    public function openCreateModal(): void
    {
        Log::info('openCreateModal method called');
        $this->isEditMode = false;
        $this->denomination = null;
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(Denomination $denomination): void
    {
        $this->isEditMode = true;
        $this->denomination = $denomination;
        $this->loadDenominationData();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->reset(['denomination', 'isEditMode']);
    }

    public function loadDenominationData(): void
    {
        if ($this->denomination) {
            $this->name = $this->denomination->name;
            $this->value = (float) $this->denomination->value;
            $this->type = $this->denomination->type;
            // currency removed
            $this->description = $this->denomination->description ?? '';
            $this->is_active = $this->denomination->is_active;
        }
    }

    public function resetForm(): void
    {
        $this->reset([
            'name',
            'value',
            'type',
            // currency removed
            'description',
            'is_active',
        ]);
        
        $this->value = 0.00;
        $this->type = 'coin';
        // currency removed
        $this->is_active = true;
    }

    public function save(): void
    {
        if (!user_can('Manage Cash Denominations')) {
            $this->alert('error', __('cashregister::messages.errorOccurred'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'value' => $this->value,
                'type' => $this->type,
                // currency removed
                'description' => $this->description ?: null,
                'is_active' => $this->is_active,
                'branch_id' => branch()->id ?? null,
                'restaurant_id' => restaurant()->id ?? null,
            ];

            if ($this->isEditMode && $this->denomination) {
                $this->denomination->update($data);
                $message = __('cashregister::messages.denominationUpdated');
                $event = 'denominationUpdated';
            } else {
                Denomination::create($data);
                $message = __('cashregister::messages.denominationCreated');
                $event = 'denominationCreated';
            }

            $this->alert('success', $message, [
                'toast' => true,
                'position' => 'top-end',
            ]);

            $this->closeModal();
            $this->dispatch($event);
            $this->dispatch('refreshTable');

        } catch (\Exception $e) {
            $this->alert('error', __('cashregister::messages.errorOccurred'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function getPreviewAttribute(): string
    {
        $typeLabel = match ($this->type) {
            'coin' => __('cashregister::app.coin'),
            'note' => __('cashregister::app.note'),
            'bill' => __('cashregister::app.bill'),
            default => __('cashregister::app.unknown'),
        };

        return $typeLabel . ' ' . number_format($this->value, 2);
    }

    #[On('editDenomination')]
    public function handleEditDenomination($denominationId): void
    {
        $denomination = Denomination::findOrFail($denominationId);
        $this->openEditModal($denomination);
    }

    public function render()
    {
        return view('cashregister::livewire.denominations.denominations-form', [
            'types' => Denomination::getAvailableTypes(),
        ]);
    }
}
