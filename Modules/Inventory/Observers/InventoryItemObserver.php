<?php

namespace Modules\Inventory\Observers;

use App\Helper\Files;
use Modules\Inventory\Entities\InventoryItem;

class InventoryItemObserver
{

    public function creating(InventoryItem $inventoryitem)
    {
        if (branch()) {
            $inventoryitem->branch_id = branch()->id;
        }
    }

    public function deleted(InventoryItem $inventoryitem): void
    {
        if ($inventoryitem->photo_path) {
            $folder = trim(dirname($inventoryitem->photo_path), './') ?: InventoryItem::PHOTO_DIRECTORY;

            Files::deleteFile(
                basename($inventoryitem->photo_path),
                $folder
            );
        }
    }
}
