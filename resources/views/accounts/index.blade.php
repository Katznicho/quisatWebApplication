<x-app-layout title="Accounts - Stabex Customer Portal">
    <x-dashboard-layout header="Accounts">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="mb-4">
                <h1 class="text-lg font-semibold">Manage Accounts</h1>
            </div>

            <div x-data="{ activeTab: 'active' }">
                <x-filament::tabs label="Account Status">
                    <x-filament::tabs.item
                        alpine-active="activeTab === 'active'"
                        x-on:click="activeTab = 'active'"
                    >
                        Active Accounts
                    </x-filament::tabs.item>

                    <x-filament::tabs.item
                        alpine-active="activeTab === 'blocked'"
                        x-on:click="activeTab = 'blocked'"
                    >
                        Blocked Accounts
                    </x-filament::tabs.item>
                </x-filament::tabs>

                <div class="mt-6">
                    <div x-show="activeTab === 'active'" x-cloak>
                        @livewire('list-accounts', ['status' => 'active'])
                    </div>

                    <div x-show="activeTab === 'blocked'" x-cloak>
                        @livewire('list-accounts', ['status' => 'blocked'])
                    </div>
                </div>
            </div>
        </div>
    </x-dashboard-layout>
</x-app-layout>


