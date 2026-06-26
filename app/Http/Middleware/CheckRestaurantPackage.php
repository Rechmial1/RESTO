<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRestaurantPackage
{

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (user() && user()->restaurant_id) {
            // Get restaurant modules to check which modules are enabled
            $restaurantModules = restaurant_modules();
            
            // Check staff, menu item, and order limits only if modules are enabled
            $staffStats = getRestaurantStaffStats(user()->restaurant_id);
            $menuItemStats = getRestaurantMenuItemStats(user()->restaurant_id);
            $orderStats = branch() ? getRestaurantOrderStats(branch()->id) : null;

            // Check if limits are exceeded only for enabled modules
            $staffExceeded = in_array('Staff', $restaurantModules) && $staffStats && !$staffStats['unlimited'] && $staffStats['current_count'] > $staffStats['staff_limit'];
            $menuItemExceeded = in_array('Menu Item', $restaurantModules) && $menuItemStats && !$menuItemStats['unlimited'] && $menuItemStats['current_count'] > $menuItemStats['menu_items_limit'];
            $orderExceeded = in_array('Order', $restaurantModules) && $orderStats && !$orderStats['unlimited'] && $orderStats['current_count'] > $orderStats['order_limit'];

            // Build allowed routes based on which limits are exceeded
            if ($staffExceeded || $menuItemExceeded || $orderExceeded) {
                $allowedRoutes = ['pricing.plan'];

                // Add staff routes if staff limit is exceeded
                if ($staffExceeded) {
                    $allowedRoutes = array_merge($allowedRoutes, [
                        'staff.index',
                        'staff.edit',
                        'staff.update',
                        'staff.destroy',
                    ]);
                }

                // Add menu item routes if menu item limit is exceeded
                if ($menuItemExceeded) {
                    $allowedRoutes = array_merge($allowedRoutes, [
                        'menu-items.index',
                        'menu-items.edit',
                        'menu-items.update',
                        'menu-items.destroy',
                    ]);
                }

                // Add order routes if order limit is exceeded
                if ($orderExceeded) {
                    $allowedRoutes = array_merge($allowedRoutes, [
                        'orders.index',
                        'orders.show',
                        'orders.edit',
                        'orders.update',
                        'orders.destroy',
                        'orders.print',
                        'orders.pdf',
                    ]);
                }

                if ($request->routeIs('settings.index')) {
                    $tab = $request->query('tab');

                    if ($tab !== 'billing') {
                        // Build appropriate warning message
                        $messages = [];
                        if ($staffExceeded) {
                            $messages[] = __('messages.staffLimitExceeded');
                        }
                        if ($menuItemExceeded) {
                            $messages[] = __('messages.menuItemLimitExceeded');
                        }
                        if ($orderExceeded) {
                            $messages[] = __('messages.orderLimitExceeded');
                        }
                        $message = implode(' ', $messages);

                        return redirect()->route('settings.index', ['tab' => 'billing'])
                            ->with('warning', $message);
                    }

                    return $next($request);
                }

                if (!$request->routeIs($allowedRoutes)) {
                    // Build appropriate warning message
                    $messages = [];
                    if ($staffExceeded) {
                        $messages[] = __('messages.staffLimitExceeded');
                    }
                    if ($menuItemExceeded) {
                        $messages[] = __('messages.menuItemLimitExceeded');
                    }
                    if ($orderExceeded) {
                        $messages[] = __('messages.orderLimitExceeded');
                    }
                    $message = implode(' ', $messages);

                    return redirect()->route('settings.index', ['tab' => 'billing'])
                        ->with('warning', $message);
                }
            }

            // Prevent adding new staff if limit reached (only if Staff module is enabled)
            if (in_array('Staff', $restaurantModules) && $staffStats && !$staffStats['unlimited']) {
                $canAdd = $staffStats['current_count'] < $staffStats['staff_limit'];
                if ($request->routeIs(['staff.create', 'staff.store']) && !$canAdd) {
                    abort(403);
                }
            }

            // Prevent adding new menu items if limit reached (only if Menu Item module is enabled)
            if (in_array('Menu Item', $restaurantModules) && $menuItemStats && !$menuItemStats['unlimited']) {
                $canAdd = $menuItemStats['current_count'] < $menuItemStats['menu_items_limit'];
                if ($request->routeIs(['menu-items.create', 'menu-items.store', 'menu-items.bulk-import']) && !$canAdd) {
                    abort(403);
                }
            }

            // Prevent adding new orders if limit reached (only if Order module is enabled)
            if (in_array('Order', $restaurantModules) && $orderStats && !$orderStats['unlimited']) {
                $canAdd = $orderStats['current_count'] < $orderStats['order_limit'];
                if ($request->routeIs(['orders.create', 'orders.store', 'pos.store']) && !$canAdd) {
                    abort(403);
                }
            }
        }

        return $next($request);
    }

}
