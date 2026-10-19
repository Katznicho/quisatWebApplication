<x-app-layout title="Analytics - GreensWell">
    <x-dashboard-layout header="Analytics Dashboard">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">System Analytics</h2>
                <p class="text-sm text-gray-500 mt-1">Track performance and metrics across all businesses</p>
            </div>

            @livewire('analytics-dashboard')
        </div>
    </x-dashboard-layout>
</x-app-layout>
