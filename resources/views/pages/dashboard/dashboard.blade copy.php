<x-app-layout>
    <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-au to">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">Dashboard</h1>
            </div>

            <!-- Right: Actions -->
            <div class="grid grid-flow-col sm:auto-cols-max justify-start sm:justify-end gap-2">

                <!-- Filter button -->
                <x-dropdown-filter align="right" />

                <!-- Datepicker built with flatpickr -->
                <x-datepicker />


            </div>

        </div>

        <!-- Cards -->
        <div class="grid grid-cols-12 gap-6">
            <!-- Line chart (Total Users) -->
            <x-dashboard.dashboard-card-01 :dataFeed="$dataFeed" :users="$users" />

            <!-- Line chart (Total Accounts) -->
            <x-dashboard.dashboard-card-02 :dataFeed="$dataFeed" :fundraisers="$fundraisers" />

            <!-- Line chart (Total Transactions) -->
            <x-dashboard.dashboard-card-03 :dataFeed="$dataFeed" :transactionsAmount="$transactionsAmount" />

            <!-- Bar chart (Personal vs Group) -->
            <x-dashboard.dashboard-card-04 />

            <!-- Line chart (Real Time Donations) -->
            <x-dashboard.dashboard-card-05 />
            <!-- Stacked bar chart (Top Ups VS Withraws) -->
            <x-dashboard.dashboard-card-09 />

            <!-- Card (Recent Activity) -->
            <x-dashboard.dashboard-card-12 />

            <!-- Table (Top Campaigns) -->
            <x-dashboard.dashboard-card-07 />
        </div>

    </div>
</x-app-layout>