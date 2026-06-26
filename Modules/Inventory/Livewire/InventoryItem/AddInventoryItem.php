<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Entities\Unit;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\Supplier;
use Livewire\WithFileUploads;
use App\Helper\Files;

class AddInventoryItem extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    
    public $name;
    public $itemCategory;
    public $unit;
    public $thresholdQuantity = 0;
    public $preferredSupplier;
    public $itemCategories;
    public $units;
    public $suppliers;
    public $reorderQuantity = 0;
    public $unitPurchasePrice = 0;
    public $description = '';
    public $photo;

    protected $listeners = [
        'preferredSupplier-selected' => 'onPreferredSupplierSelected'
    ];

    public function mount()
    {
        $this->itemCategories = InventoryItemCategory::all();
        $this->units = Unit::all();
        $this->suppliers = Supplier::all();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'itemCategory' => 'required|',
            'unit' => 'required',
            'thresholdQuantity' => 'required|numeric|min:0',
            'preferredSupplier' => 'required',
            'reorderQuantity' => 'required|numeric|min:0',
            'unitPurchasePrice' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'photo' => 'nullable|image|max:2048',
        ];
    }

    public function submitForm()
    {
        $this->validate();

        $photoPath = null;

        if ($this->photo) {
            $uploadedFileName = Files::uploadLocalOrS3($this->photo, InventoryItem::PHOTO_DIRECTORY);
            $photoPath = InventoryItem::PHOTO_DIRECTORY . '/' . $uploadedFileName;
        }

        InventoryItem::create([
            'name' => $this->name,
            'description' => $this->description,
            'inventory_item_category_id' => $this->itemCategory,
            'unit_id' => $this->unit,
            'threshold_quantity' => $this->thresholdQuantity,
            'preferred_supplier_id' => $this->preferredSupplier,
            'reorder_quantity' => $this->reorderQuantity,
            'unit_purchase_price' => $this->unitPurchasePrice,
            'photo_path' => $photoPath,
        ]);

        $this->dispatch('inventoryItemAdded');
        $this->reset([
            'name',
            'itemCategory',
            'unit',
            'thresholdQuantity',
            'preferredSupplier',
            'reorderQuantity',
            'unitPurchasePrice',
            'description',
            'photo',
        ]);
        $this->showAddInventoryItem = false;

        $this->alert('success', __('inventory::modules.inventoryItem.inventoryItemAdded'));
    }

    public function clearSelection()
    {
        $this->preferredSupplier = null;
    }


    public function onPreferredSupplierSelected($itemId)
    {
        $this->preferredSupplier = $itemId;
    }

    public function updatedPhoto()
    {
        $this->validateOnly('photo');
    }

    public function render()
    {
        return view('inventory::livewire.inventory-item.add-inventory-item');
    }
}
