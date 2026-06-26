<?php

namespace App\Livewire\Menu;

use App\Models\Menu;
use Livewire\Component;
use App\Models\MenuItem;
use Livewire\Attributes\On;
use App\Models\ItemCategory;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use App\Scopes\AvailableMenuItemScope;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Features\SupportPagination\WithoutUrlPagination;

class MenuItems extends Component
{

    use WithPagination, WithoutUrlPagination, LivewireAlert;

    public $showEditMenuItem = false;
    public $clearFilterButton = false;
    public $showMenuCategoryModal = false;
    public $showItemVariationsModal = false;
    public $menuItem;
    public $confirmDeleteMenuItem = false;
    public $showFilters = false;
    public $menuID = null;
    public $search;
    public $categoryList = [];
    public $menus = [];
    public $filterCategories = [];
    public $filterTypes = [];
    public $filterAvailability;
    public $perPageOptions = [10, 20, 30, 50];
    #[Url]
    public $perPage = 20;
    #[Url]
    public $sortOrder = 'desc';


    public function mount()
    {
        $this->categoryList = ItemCategory::all();
        $this->menus = Menu::all();
    }


    public function showEditMenu($id)
    {
        $this->showEditMenuItem = true;
        $this->menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->findOrFail($id);
    }

    public function showItemVariations($id)
    {
        $this->showItemVariationsModal = true;
        $this->menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->findOrFail($id);
    }

    public function showDeleteMenuItem($id)
    {
        $this->confirmDeleteMenuItem = true;
        $this->menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->findOrFail($id);
    }

    public function deleteMenuItem($id)
    {
        $menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->find($id);
        $restaurantId = $menuItem && $menuItem->branch ? $menuItem->branch->restaurant_id : null;

        MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->where('id', $id)->delete();
        $languages = languages()->pluck('language_code')->toArray();

        // Clear cache for all languages for this menu item
        foreach ($languages as $locale) {
            cache()->forget("menu_item_{$id}_name_{$locale}");
            cache()->forget("menu_item_{$id}_description_{$locale}");
        }

        // Clear menu item stats cache
        if ($restaurantId) {
            cache()->forget('restaurant_' . $restaurantId . '_menu_item_stats');
        }

        $this->menuItem = null;
        $this->confirmDeleteMenuItem = false;

        $this->alert('success', __('messages.menuItemDeleted'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    #[On('showMenuCategoryModal')]
    public function showMenuCategoryModal()
    {
        $this->showMenuCategoryModal = true;
    }

    #[On('hideCategoryModal')]
    public function hideCategoryModal()
    {
        $this->showMenuCategoryModal = false;
    }

    #[On('hideEditMenuItem')]
    public function hideEditMenuItem()
    {
        $this->showEditMenuItem = false;
    }

    #[On('hideItemVariations')]
    public function hideItemVariations()
    {
        $this->showItemVariationsModal = false;
    }

    #[On('showMenuItemFilters')]
    public function showFiltersSection()
    {
        $this->showFilters = true;
    }


    public function clearFilters()
    {
        $this->filterCategories = [];
        $this->filterTypes = [];
        $this->search = '';
        $this->sortOrder = 'desc';
        $this->perPage = 10;
        $this->dispatch('clearMenuItemFilter');
    }

    public function toggleAvailability($id)
    {
        $menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->findOrFail($id);
        $menuItem->update(['is_available' => !$menuItem->is_available]);

        $this->alert('success', __('messages.menuItemUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }

    public function toggleShowOnCustomerSite($id)
    {
        $menuItem = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)->findOrFail($id);
        $menuItem->update(['show_on_customer_site' => !$menuItem->show_on_customer_site]);

        $this->alert('success', __('messages.menuItemUpdated'), [
            'toast' => true,
            'position' => 'top-end',
            'showCancelButton' => false,
            'cancelButtonText' => __('app.close')
        ]);
    }


    public function render()
    {
        $this->clearFilterButton = false;

        // Start the query with global scope disabled, eager loading, and counts
        $query = MenuItem::withoutGlobalScope(AvailableMenuItemScope::class)
            ->with(['category', 'menu'])
            ->withCount('variations')
            ->has('category')
            ->has('menu');

        if (!is_null($this->menuID)) {
            $query = $query->where('menu_id', $this->menuID);
        }

        if ($this->search != '') {
            $this->clearFilterButton = true;
        }

        if (!empty($this->filterCategories)) {
            $query = $query->whereIn('item_category_id', $this->filterCategories);
            $this->clearFilterButton = true;
        }

        if (!empty($this->filterTypes)) {
            $query = $query->whereIn('type', $this->filterTypes);
            $this->clearFilterButton = true;
        }

        if (!is_null($this->filterAvailability)) {
            $query = $query->where('is_available', $this->filterAvailability);
            $this->clearFilterButton = true;
        }

        if ($this->sortOrder != 'desc') {
            $this->clearFilterButton = true;
        }

        if ($this->perPage != 20) {
            $this->clearFilterButton = true;
        }

        $query = $query->search('item_name', $this->search)->orderBy('id', $this->sortOrder)->paginate($this->perPage);

        return view('livewire.menu.menu-items', [
            'menuItems' => $query
        ]);
    }
}
