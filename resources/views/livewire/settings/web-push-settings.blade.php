<div>
    <div
        class="p-4 mx-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">@lang('modules.settings.webPushSetting')</h3>
        <form wire:submit="save">
            <div class="grid gap-6">
                @if ($public_key && $private_key)
                    <div class="grid grid-cols-2 gap-x-4">
                        <div>
                            <x-label for="public_key" :value="__('modules.settings.vapidPublicKey')" required/>
                            <x-input id="public_key" class="block w-full mt-1 bg-gray-100 cursor-not-allowed" wire:model='public_key' disabled />
                            <x-input-error for="public_key" class="mt-2" />
                        </div>
        
                        <div>
                            <x-label for="private_key" :value="__('modules.settings.vapidPrivateKey')" required/>
                            <x-input id="private_key" class="block w-full mt-1 bg-gray-100 cursor-not-allowed" wire:model='private_key' disabled />
                            <x-input-error for="private_key" class="mt-2" />
                        </div>
                    </div>
                @endif
        
                {{-- Always show subject --}}
                <div class="grid grid-cols-2 gap-x-4">
                    <div>
                        <x-label for="subject" :value="__('modules.settings.vapidSubject')" required/>
                        <x-input id="subject" class="block w-full mt-1" wire:model='subject' />
                        <x-input-error for="subject" class="mt-2" />
                    </div>
                </div>
        
                <div class="flex gap-4">
                    <x-button>@lang('app.save')</x-button>
        
                    <x-button type="button" wire:click="generateKeys">
                        @lang('modules.settings.generateVapidKeys')
                    </x-button>
                </div>
            </div>
        </form>

    </div>
</div>
