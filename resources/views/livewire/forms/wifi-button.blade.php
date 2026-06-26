<div>
    <!-- WiFi Button -->
    <button wire:click="openWifiModal" type="button"
        class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5"
        data-tooltip-target="tooltip-wifi">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
        </svg>
    </button>

    <div id="tooltip-wifi" role="tooltip"
        class="absolute z-10 invisible inline-block px-3 py-2 text-sm font-medium text-white transition-opacity duration-300 bg-gray-900 rounded-lg shadow-sm opacity-0 tooltip">
        @lang('menu.wifi')
        <div class="tooltip-arrow" data-popper-arrow></div>
    </div>

    <!-- WiFi Modal -->
    @if ($showWifiModal)
        <div class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 !m-0">
            <div class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-md p-6 mx-2 md:m-0 max-w-md w-full">
                <!-- Modal Header -->
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-skin-base" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">@lang('menu.wifi')</h3>
                    </div>
                    <button wire:click="closeWifiModal" type="button"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            @lang('modules.settings.wifiName')
                        </label>
                        <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                            </svg>
                            <span class="text-gray-900 dark:text-white font-medium flex-1">{{ $restaurant->wifi_name }}</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            @lang('modules.settings.wifiPassword')
                        </label>
                        <div class="flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <span class="text-gray-900 dark:text-white font-medium font-mono flex-1">{{ $restaurant->wifi_password }}</span>
                            <button id="wifi-copy-button" type="button" onclick="copyWifiPassword('{{ $restaurant->wifi_password }}')" 
                                class="inline-flex items-center text-skin-base hover:text-skin-base/80 focus:outline-none px-2 py-1 rounded transition-colors">
                                <svg id="wifi-copy-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path id="wifi-copy-icon-path" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="mt-6 flex justify-end">
                    <x-button wire:click="closeWifiModal">
                        @lang('app.close')
                    </x-button>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    function copyWifiPassword(text) {
        const copyIcon = document.getElementById('wifi-copy-icon');
        const copyPath = document.getElementById('wifi-copy-icon-path');
        
        if (!copyIcon || !copyPath) return;

        // Store original path d attribute
        const originalD = copyPath.getAttribute('d');

        // Copy to clipboard
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                // Change icon to checkmark
                copyPath.setAttribute('d', 'M5 13l4 4L19 7');
                
                // Revert back after 2 seconds
                setTimeout(() => {
                    copyPath.setAttribute('d', originalD);
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
            });
        } else {
            // Fallback for older browsers
            const tempTextArea = document.createElement('textarea');
            tempTextArea.value = text;
            document.body.appendChild(tempTextArea);
            tempTextArea.select();
            tempTextArea.setSelectionRange(0, 99999);
            document.execCommand('copy');
            document.body.removeChild(tempTextArea);

            // Change icon to checkmark
            copyPath.setAttribute('d', 'M5 13l4 4L19 7');
            
            // Revert back after 2 seconds
            setTimeout(() => {
                copyPath.setAttribute('d', originalD);
            }, 2000);
        }
    }
</script>

