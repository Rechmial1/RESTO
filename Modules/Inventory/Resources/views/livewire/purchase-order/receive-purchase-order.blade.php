<div>
    <x-modal wire:model="showModal" maxWidth="4xl">
        <div class="p-6">
            <div class="text-lg font-medium text-gray-900 mb-6">
                {{ trans('inventory::modules.purchaseOrder.receive_title') }} #{{ $purchaseOrder->po_number ?? '' }}
            </div>

            <form wire:submit.prevent="receive">
                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ trans('inventory::modules.inventoryItem.name') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ trans('inventory::modules.purchaseOrder.ordered_quantity') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ trans('inventory::modules.purchaseOrder.previously_received') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ trans('inventory::modules.purchaseOrder.receiving_quantity') }}
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ trans('inventory::modules.purchaseOrder.remaining') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($items as $index => $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <div class="flex items-start gap-3">
                                            <div class="h-12 w-12 rounded-md overflow-hidden bg-gray-50 border border-gray-200 flex-shrink-0">
                                                @if(!empty($item['photo_url']))
                                                    <img src="{{ $item['photo_url'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                                                @else
                                                    <div class="h-full w-full flex items-center justify-center text-gray-400">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11l2 2 4-4m2 7h.01" />
                                                        </svg>
                                                    </div>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900">{{ $item['name'] }}</p>
                                                <p class="text-sm text-gray-500">
                                                    {{ $item['description'] ?? __('inventory::modules.inventoryItem.noDescription') }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($item['quantity'], 2) }}
                                        @if(!empty($item['unit']))
                                            <span class="text-xs text-gray-400">({{ $item['unit'] }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($item['received_quantity'], 2) }}
                                        @if(!empty($item['unit']))
                                            <span class="text-xs text-gray-400">({{ $item['unit'] }})</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <x-input type="number" 
                                                wire:model="items.{{ $index }}.receiving_quantity"
                                                step="0.001" 
                                                min="0" 
                                                {{-- max="{{ $item['quantity'] - $item['received_quantity'] }}" --}}
                                                class="block w-32" />
                                        @error("items.{$index}.receiving_quantity")
                                            <span class="text-red-500 text-sm">{{ $message }}</span>
                                        @enderror
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ number_format($item['quantity'] - $item['received_quantity'] - ($items[$index]['receiving_quantity'] ?? 0), 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end space-x-3">
                    <x-secondary-button wire:click="$set('showModal', false)" wire:loading.attr="disabled">
                        {{ trans('app.cancel') }}
                    </x-secondary-button>

                    <x-button type="submit" wire:loading.attr="disabled">
                        {{ trans('inventory::modules.purchaseOrder.receive_items') }}
                    </x-button>
                </div>
            </form>
        </div>
    </x-modal>
</div> 