<?php

namespace Modules\Inventory\Livewire\InventoryItem;

use Livewire\Component;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Entities\Unit;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Modules\Inventory\Entities\Supplier;
use Livewire\WithFileUploads;
use App\Helper\Files;
use Modules\Inventory\Entities\InventoryItem;

class EditInventoryItem extends Component
{
    use LivewireAlert;
    use WithFileUploads;
    
    public $inventoryItem;
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
    public $removeExistingPhoto = false;

    protected $listeners = [
        'preferredSupplier-selected' => 'onPreferredSupplierSelected'
    ];

    public function mount($inventoryItem)
    {
        $this->inventoryItem = $inventoryItem;
        $this->name = $inventoryItem->name;
        $this->itemCategory = $inventoryItem->inventory_item_category_id;
        $this->unit = $inventoryItem->unit_id;
        $this->thresholdQuantity = $inventoryItem->threshold_quantity;
        $this->preferredSupplier = $inventoryItem->preferred_supplier_id;
        $this->reorderQuantity = $inventoryItem->reorder_quantity;
        $this->unitPurchasePrice = $inventoryItem->unit_purchase_price;
        $this->description = $inventoryItem->description;
        $this->itemCategories = InventoryItemCategory::all();
        $this->units = Unit::all();
        $this->suppliers = Supplier::all();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'itemCategory' => 'required',
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

        $updatePayload = [
            'name' => $this->name,
            'description' => $this->description,
            'inventory_item_category_id' => $this->itemCategory,
            'unit_id' => $this->unit,
            'threshold_quantity' => $this->thresholdQuantity,
            'preferred_supplier_id' => $this->preferredSupplier,
            'reorder_quantity' => $this->reorderQuantity,
            'unit_purchase_price' => $this->unitPurchasePrice,
        ];

        if ($this->photo) {
            $newFileName = Files::uploadLocalOrS3($this->photo, InventoryItem::PHOTO_DIRECTORY);

            if ($this->inventoryItem->photo_path) {
                Files::deleteFile(
                    basename($this->inventoryItem->photo_path),
                    trim(dirname($this->inventoryItem->photo_path), './') ?: InventoryItem::PHOTO_DIRECTORY
                );
            }

            $updatePayload['photo_path'] = InventoryItem::PHOTO_DIRECTORY . '/' . $newFileName;
            $this->removeExistingPhoto = false;
        } elseif ($this->removeExistingPhoto && $this->inventoryItem->photo_path) {
            Files::deleteFile(
                basename($this->inventoryItem->photo_path),
                trim(dirname($this->inventoryItem->photo_path), './') ?: InventoryItem::PHOTO_DIRECTORY
            );

            $updatePayload['photo_path'] = null;
        }

        $this->inventoryItem->update($updatePayload);
        $this->inventoryItem->refresh();
        $this->photo = null;
        $this->removeExistingPhoto = false;

        $this->dispatch('hideEditInventoryItemModal');

        $this->alert('success', __('inventory::modules.inventoryItem.inventoryItemUpdated'));
    }

    public function onPreferredSupplierSelected($itemId, $field = null)
    {
        $this->preferredSupplier = $itemId;
    }

    public function removePhoto(): void
    {
        $this->photo = null;
        $this->removeExistingPhoto = true;
    }

    public function updatedPhoto(): void
    {
        $this->removeExistingPhoto = false;
        $this->validateOnly('photo');
    }

    public function render()
    {
        return view('inventory::livewire.inventory-item.edit-inventory-item');
    }
}
