<x-app-layout title="Business Analytics - {{ $business->name }} - GreensWell">
    <x-dashboard-layout header="Business Analytics: {{ $business->name }}">
        @livewire('analytics-business-detail', ['business' => $business])
    </x-dashboard-layout>
</x-app-layout>

