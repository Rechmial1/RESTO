<?php

namespace Modules\CashRegister\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Modules\CashRegister\Livewire\CashierWidget;
use Modules\CashRegister\Livewire\Dashboard\RegisterDashboard;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CashRegisterServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'CashRegister';

    protected string $nameLower = 'cashregister';

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
        $this->loadMigrationsFrom(module_path($this->name, 'Database/Migrations'));

        // Ensure the module appears in custom_module_plugins() cache
        cache()->forget('custom_module_plugins');

        // Add EnforceRegisterOpen middleware to web group when module is enabled
        if (function_exists('module_enabled') && module_enabled('CashRegister')) {
            $this->app['router']->aliasMiddleware('enforce.register.open', \Modules\CashRegister\Http\Middleware\EnforceRegisterOpen::class);
            // Defer pushing to group until after the app is fully booted so groups are defined
            $this->app->booted(function () {
                $this->app['router']->pushMiddlewareToGroup('web', \Modules\CashRegister\Http\Middleware\EnforceRegisterOpen::class);
            });
        }

        // Wire up Payment model event to sync cash payments to register
        if (class_exists(\App\Models\Payment::class)) {
            \App\Models\Payment::created(function ($payment) {
                try {
                    // Only on paid cash payments with an order relation
                    $isCash = ($payment->payment_method ?? null) === 'cash';
                    $order = method_exists($payment, 'order') ? $payment->order : null;
                    $orderStatus = $order ? ($order->status ?? $order->payment_status ?? null) : null;
                    $isPaid = $orderStatus === 'paid';
                    if ($isCash && $order && $isPaid) {
                        \Modules\CashRegister\Services\CashRegisterOrderSyncService::syncCashForOrder($order);
                    }
                } catch (\Throwable $e) {
                    Log::error('[CashRegister] Failed syncing payment to cash register', [
                        'payment_id' => $payment->id ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
            // Also when order transitions to paid
            if (class_exists(\App\Models\Order::class)) {
                \App\Models\Order::saved(function ($order) {
                    try {
                        $status = $order->status ?? $order->payment_status ?? null;
                        if ($status === 'paid') {
                            \Modules\CashRegister\Services\CashRegisterOrderSyncService::syncCashForOrder($order->fresh(['payments']));
                        }
                        // If becomes unpaid, clean up any transaction
                        if ($status !== 'paid') {
                            \Modules\CashRegister\Services\CashRegisterOrderSyncService::syncCashForOrder($order->fresh(['payments']));
                        }
                    } catch (\Throwable $e) {
                        Log::error('[CashRegister] Failed syncing order to cash register', [
                            'order_id' => $order->id ?? null,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });
            }
        }
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        // Ensure alias exists for potential route usage inside the module
        $this->app['router']->aliasMiddleware('force.open.register', \Modules\CashRegister\Http\Middleware\EnforceRegisterOpen::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        $this->commands([
            \Modules\CashRegister\Console\Commands\SeedCashRegisterDataCommand::class,
        ]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void
    {
        // $this->app->booted(function () {
        //     $schedule = $this->app->make(Schedule::class);
        //     $schedule->command('inspire')->hourly();
        // });
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'Resources/lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'Resources/lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'Resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\' . $this->name . '\\View\\Components', $this->nameLower);

        Livewire::component('cash-register.dashboard.register-dashboard', RegisterDashboard::class);
        Livewire::component('cash-register.cashier-widget', CashierWidget::class);
        Livewire::component('cash-register.approvals-list', \Modules\CashRegister\Livewire\Approvals\ApprovalsList::class);
        Livewire::component('cash-register.reports.x-report', \Modules\CashRegister\Livewire\Reports\XReport::class);
        Livewire::component('cash-register.reports.discrepancy-report', \Modules\CashRegister\Livewire\Reports\DiscrepancyReport::class);
        Livewire::component('cash-register.reports.z-report', \Modules\CashRegister\Livewire\Reports\ZReport::class);
        Livewire::component('cash-register.reports.cash-ledger-report', \Modules\CashRegister\Livewire\Reports\CashLedgerReport::class);
        Livewire::component('cash-register.reports.cash-in-out-report', \Modules\CashRegister\Livewire\Reports\CashInOutReport::class);
        Livewire::component('cash-register.reports.shift-summary-report', \Modules\CashRegister\Livewire\Reports\ShiftSummaryReport::class);

        // Denominations components
        Livewire::component('cashregister::denominations.denominations', \Modules\CashRegister\Livewire\Denominations\Denominations::class);
        Livewire::component('cashregister::denominations.denominations-table', \Modules\CashRegister\Livewire\Denominations\DenominationsTable::class);
        Livewire::component('cashregister::denominations.denominations-form', \Modules\CashRegister\Livewire\Denominations\DenominationsForm::class);

        // Settings components
        Livewire::component('cashregister::settings.register-settings', \Modules\CashRegister\Livewire\Settings\RegisterSettings::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
