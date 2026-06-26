<?php

namespace Modules\Inventory\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Events\NewOrderCreated;
use App\Events\OrderCancelled;
use Modules\Inventory\Listeners\UpdateInventoryOnOrderReceived;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Inventory\Livewire\Reports\UsageReport;
use Modules\Inventory\Livewire\Reports\TurnoverReport;
use Modules\Inventory\Livewire\Reports\ForecastingReport;
use Modules\Inventory\Livewire\Reports\PurchaseOrderReport;
use Modules\Inventory\Console\CreateAutoPurchaseOrder;
use Modules\Inventory\Console\ActivateModuleCommand;
use Modules\Inventory\Console\CheckBatchExpiry;
use Illuminate\Console\Scheduling\Schedule;
use Modules\Inventory\Entities\InventoryItem;
use Modules\Inventory\Observers\InventoryItemObserver;
use Modules\Inventory\Entities\Unit;
use Modules\Inventory\Observers\UnitObserver;
use Modules\Inventory\Entities\InventoryItemCategory;
use Modules\Inventory\Observers\InventoryItemCategoryObserver;
use Modules\Inventory\Entities\Supplier;
use Modules\Inventory\Observers\SupplierObserver;
use Modules\Inventory\Entities\InventoryStock;
use Modules\Inventory\Observers\InventoryStockObserver;
use Modules\Inventory\Entities\InventoryMovement;
use Modules\Inventory\Observers\InventoryMovementObserver;
use App\Events\NewRestaurantCreatedEvent;
use Modules\Inventory\Listeners\CreateInventoryOnRestaurantCreatedListener;
use App\Models\Branch;
use Modules\Inventory\Observers\BranchObserver;
use Modules\Inventory\Listeners\UpdateInventoryOnOrderCancelled;
use Modules\Inventory\Livewire\Components\SearchableSelect;
use Modules\Inventory\Livewire\Reports\BatchExpectedVsActualReport;
use Modules\Inventory\Livewire\Reports\BatchWasteReport;
use Modules\Inventory\Livewire\Reports\BatchCogsReport;

class InventoryServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Inventory';

    protected string $nameLower = 'inventory';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Inventory', 'Database/Migrations'));

        // Register event listeners
        Event::listen(NewOrderCreated::class, UpdateInventoryOnOrderReceived::class);
        Event::listen(NewRestaurantCreatedEvent::class, CreateInventoryOnRestaurantCreatedListener::class);
        Event::listen(OrderCancelled::class, UpdateInventoryOnOrderCancelled::class);

        Livewire::component('inventory::reports.usage-report', UsageReport::class);
        Livewire::component('inventory::reports.turnover-report', TurnoverReport::class);
        Livewire::component('inventory::reports.forecasting-report', ForecastingReport::class);
        Livewire::component('inventory::reports.purchase-order-report', PurchaseOrderReport::class);
        Livewire::component('inventory::reports.batch-production-report', \Modules\Inventory\Livewire\Reports\BatchProductionReport::class);
        Livewire::component('inventory::reports.batch-consumption-report', \Modules\Inventory\Livewire\Reports\BatchConsumptionReport::class);
        Livewire::component('inventory::reports.batch-expected-vs-actual-report', BatchExpectedVsActualReport::class);
        Livewire::component('inventory::reports.batch-waste-report', BatchWasteReport::class);
        Livewire::component('inventory::reports.batch-cogs-report', BatchCogsReport::class);
        Livewire::component('inventory::batch-recipes.batch-recipes-list', \Modules\Inventory\Livewire\BatchRecipes\BatchRecipesList::class);
        Livewire::component('inventory::batch-recipes.batch-recipe-form', \Modules\Inventory\Livewire\BatchRecipes\BatchRecipeForm::class);
        Livewire::component('inventory::batch-recipes.produce-batch', \Modules\Inventory\Livewire\BatchRecipes\ProduceBatch::class);
        Livewire::component('inventory::batch-recipes.batch-inventory-list', \Modules\Inventory\Livewire\BatchRecipes\BatchInventoryList::class);
        Livewire::component('inventory::components.searchable-select', SearchableSelect::class);

        Unit::observe(UnitObserver::class);
        InventoryItem::observe(InventoryItemObserver::class);
        InventoryItemCategory::observe(InventoryItemCategoryObserver::class);
        Supplier::observe(SupplierObserver::class);
        InventoryStock::observe(InventoryStockObserver::class);
        InventoryMovement::observe(InventoryMovementObserver::class);
        Branch::observe(BranchObserver::class);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            CreateAutoPurchaseOrder::class,
            ActivateModuleCommand::class,
            CheckBatchExpiry::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('inventory:create-auto-purchase-order')->daily();
            $schedule->command('inventory:check-batch-expiry')->daily();
        });
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/inventory');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'inventory');
        } else {
            $this->loadTranslationsFrom(module_path('Inventory', 'Resources/lang'), 'inventory');
        }
    }


    protected function registerConfig()
    {
        $this->publishes([
            module_path('Inventory', 'Config/config.php') => config_path('inventory.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('Inventory', 'Config/config.php'),
            'inventory'
        );
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/inventory');

        $sourcePath = module_path('Inventory', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/inventory';
        }, \Config::get('view.paths')), [$sourcePath]), 'inventory');
    }


    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }
}
