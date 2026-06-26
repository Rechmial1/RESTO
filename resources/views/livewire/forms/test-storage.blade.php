<div>
    <form wire:submit="submitForm">
        @csrf
        <div class="space-y-4">

            @if($fileUrl)
                <x-alert type="success">
                    <div class="flex justify-between w-full">
                        {{ __('messages.fileUploaded') }}
                        <a href="{{ $fileUrl }}" target="_blank" class="underline underline-offset-1">@lang('app.view')</a>
                    </div>
                </x-alert>
            </div>
            @endif

            <!-- Error Message -->
            @error('file_error')
                <x-alert type="danger">
                    <div>
                        {!! $message !!}
                    </div>
                </x-alert>
            @enderror

            <div>
                <x-label for="file" value="{{ __('modules.settings.testStorageFile') }}" />

                <input
                    class="block w-full text-sm border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 text-slate-500 mt-1"
                    type="file" wire:model="file">

                <x-input-error for="file" class="mt-2" />

                <!-- Upload Progress -->
                <div wire:loading wire:target="file" class="mt-2">
                    <div class="flex items-center space-x-2 text-sm text-blue-600 dark:text-blue-400">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>@lang('app.uploading')...</span>
                    </div>
                    <div class="mt-2 bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                        <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                    </div>
                </div>
            </div>

        </div>

        <div class="flex w-full pb-4 space-x-4 mt-6 rtl:space-x-reverse">
            <x-button wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed">
                <span wire:loading.disabled wire:target="file">@lang('app.save')</span>
            </x-button>
            <x-button-cancel wire:click="$dispatch('hideTestStorageModal')" wire:loading.attr="disabled">@lang('app.cancel')</x-button-cancel>
        </div>
    </form>
</div>
