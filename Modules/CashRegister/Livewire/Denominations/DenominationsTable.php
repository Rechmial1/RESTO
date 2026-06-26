<?php

declare(strict_types=1);

namespace Modules\CashRegister\Livewire\Denominations;

use Modules\CashRegister\Entities\Denomination;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

final class DenominationsTable extends Component
{
    use WithPagination, LivewireAlert;

    // Search and filter properties
    public string $search = '';
    public string $typeFilter = '';
    public string $statusFilter = '';
    // currency removed
    public string $sortBy = 'value';
    public string $sortDirection = 'asc';

    // Pagination
    public int $perPage = 10;

    // Bulk actions
    public array $selectedDenominations = [];
    public bool $selectAll = false;

    // Modal states
    public bool $showDeleteModal = false;
    public bool $showBulkDeleteModal = false;
    public ?Denomination $denominationToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        // currency removed
        'sortBy' => ['except' => 'value'],
        'sortDirection' => ['except' => 'asc'],
    ];

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    // currency removed

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function sortByField(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedDenominations = $this->getDenominations()->pluck('id')->toArray();
        } else {
            $this->selectedDenominations = [];
        }
    }

    public function updatedSelectedDenominations(): void
    {
        $this->selectAll = false;
    }

    public function getDenominations(): LengthAwarePaginator
    {
        $query = Denomination::query()
            ->where(function (Builder $query) {
                $query->where('restaurant_id', restaurant()->id ?? null)
                    ->orWhereNull('restaurant_id');
            });

        // Apply search
        if ($this->search) {
            $query->where(function (Builder $query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%')
                    ->orWhere('value', 'like', '%' . $this->search . '%');
            });
        }

        // Apply filters
        if ($this->typeFilter) {
            $query->where('type', $this->typeFilter);
        }

        if ($this->statusFilter) {
            $query->where('is_active', $this->statusFilter === 'active');
        }

        // currency removed

        // Apply sorting
        if ($this->sortBy === 'value') {
            // Ensure numeric sorting for value field
            $query->orderByRaw('CAST(value AS DECIMAL(12,2)) ' . $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return $query->paginate($this->perPage);
    }

    public function confirmDelete(int $denominationId): void
    {
        $this->denominationToDelete = Denomination::find($denominationId);
        if ($this->denominationToDelete) {
            $this->showDeleteModal = true;
        }
    }

    public function deleteDenomination(): void
    {
        if ($this->denominationToDelete) {
            $this->denominationToDelete->delete();
            $this->alert('success', __('cashregister::messages.denominationDeleted'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            $this->showDeleteModal = false;
            $this->denominationToDelete = null;
        }
    }

    public function confirmBulkDelete(): void
    {
        if (count($this->selectedDenominations) > 0) {
            $this->showBulkDeleteModal = true;
        }
    }

    public function closeModal(): void
    {
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
        $this->denominationToDelete = null;
    }

    public function bulkDelete(): void
    {
        $count = Denomination::whereIn('id', $this->selectedDenominations)->delete();
        $this->alert('success', __('cashregister::messages.denominationsDeleted', ['count' => $count]), [
            'toast' => true,
            'position' => 'top-end',
        ]);
        $this->showBulkDeleteModal = false;
        $this->selectedDenominations = [];
        $this->selectAll = false;
    }

    public function toggleStatus(int $denominationId): void
    {
        $denomination = Denomination::find($denominationId);
        if ($denomination) {
            $denomination->update(['is_active' => !$denomination->is_active]);
            $status = $denomination->is_active ? 'active' : 'inactive';
            $this->alert('success', __('cashregister::messages.denominationStatusUpdated', ['status' => $status]), [
                'toast' => true,
                'position' => 'top-end',
            ]);
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'typeFilter', 'statusFilter']);
        $this->resetPage();
    }

    public function refreshTable(): void
    {
        $this->resetPage();
    }
    
    public function resetSelection(): void
    {
        $this->selectedDenominations = [];
        $this->selectAll = false;
    }
    
    #[On('refreshTable')]
    public function handleRefreshTable(): void
    {
        $this->refreshTable();
    }

    public function openCreateModal(): void
    {
        if (!user_can('Manage Cash Denominations')) {
            $this->alert('error', __('cashregister::messages.errorOccurred'), [
                'toast' => true,
                'position' => 'top-end',
            ]);
            return;
        }
        $this->dispatch('openCreateModal');
    }

    public function render()
    {
        return view('cashregister::livewire.denominations.denominations-table', [
            'denominations' => $this->getDenominations(),
            'types' => Denomination::getAvailableTypes(),
            // currency removed
        ]);
    }

    // currency methods removed
}
