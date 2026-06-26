<div>
    <div class="mx-4 p-4 mb-4 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 sm:p-6 dark:bg-gray-800">
        <h3 class="mb-4 text-xl font-semibold dark:text-white">Associations d'entreprises</h3>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">Code a communiquer a l'entreprise primaire</div>
                <div class="mt-2 text-2xl font-bold tracking-widest text-gray-900 dark:text-white">
                    {{ $settings->association_code }}
                </div>
            </div>

            <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700">
                <div class="text-sm text-gray-500 dark:text-gray-400">Statut</div>
                <div class="mt-2 text-base font-semibold text-gray-900 dark:text-white">
                    @if($settings->is_primary)
                        Entreprise primaire
                    @elseif($primaryRestaurant)
                        Secondaire de {{ $primaryRestaurant->name }}
                    @else
                        Non associee
                    @endif
                </div>

                @if(!$settings->is_primary && !$primaryRestaurant)
                    <x-button class="mt-4" wire:click="makePrimary">
                        Definir comme primaire
                    </x-button>
                @endif
            </div>
        </div>

        @if($settings->is_primary)
            <div class="mt-6">
                <h4 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
                    Ajouter une entreprise secondaire
                </h4>

                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="flex-1">
                        <x-input class="block w-full uppercase" maxlength="5" wire:model="secondaryCode"
                            placeholder="Code a 5 caracteres" />
                        <x-input-error for="secondaryCode" class="mt-2" />
                    </div>
                    <x-button wire:click="addSecondary">
                        Ajouter
                    </x-button>
                </div>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                        Entreprises secondaires liees
                    </h4>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $secondaryRestaurants->count() }}/5
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Nom</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Code</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-300">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                            @forelse($secondaryRestaurants as $secondary)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $secondary->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $secondary->email }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold tracking-widest text-gray-900 dark:text-white">{{ $secondary->association_code }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <x-danger-button wire:click="removeSecondary({{ $secondary->id }})">
                                            Supprimer
                                        </x-danger-button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Aucune entreprise secondaire liee.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
