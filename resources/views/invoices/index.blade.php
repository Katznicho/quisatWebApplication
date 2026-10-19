<x-app-layout title="Invoices - Stabex Customer Portal">
    <x-dashboard-layout header="Invoices">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <h1 class="text-lg font-semibold mb-6 text-[#322f90]">Customer Invoices</h1>
            @livewire('list-invoices')
        </div>
    </x-dashboard-layout>
</x-app-layout>
