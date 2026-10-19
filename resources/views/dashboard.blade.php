<x-app-layout title="Dashboard - Stabex Customer Portal">
    <x-dashboard-layout header="Dashboard">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <!-- Welcome Banner (same format as Customer Portal V2) -->
            <div class="relative bg-[#322f90] p-4 sm:p-6 rounded-lg overflow-hidden mb-8">
                <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl md:text-3xl text-white font-bold mb-1">
                            Welcome back, {{ session('fullName') }} 👋
                        </h1>
                        <p class="text-white/90">
                            {{ session('customerNumber') }} @if(session('phoneNumber')) ({{ session('phoneNumber') }}) @endif
                        </p>
                    </div>
                    <div class="mt-4 sm:mt-0 text-right">
                        <div class="text-white/90 text-sm mb-1" id="currentDate"></div>
                        <div class="text-white text-2xl md:text-3xl font-bold" id="currentTime"></div>
                    </div>
                </div>
            </div>

            <!-- Business Overview (copied from old Customer_Portal_V2 dashboard) -->
            <div class="grid grid-cols-12 gap-6 mb-8">
                <!-- Customer Info - First -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-[#322f90] h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-[#322f90] text-lg font-semibold">Customer Details</div>
                        </div>
                        <div class="flex-1 flex flex-col justify-center">
                            <div class="text-sm text-gray-600">
                                <p class="mb-1">
                                    <span class="font-semibold">Number:</span>
                                    {{ $response->customerNo ?? session('dashboard_customerNo') ?? 'N/A' }}
                                </p>
                                <p>
                                    <span class="font-semibold">Name:</span>
                                    {{ $response->customerName ?? session('dashboard_customerName') ?? 'N/A' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stations -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <a href="{{ route('locations.index') }}" class="block h-full">
                        <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-[#322f90] hover:shadow-xl transition-shadow duration-200 h-full flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-[#322f90] text-lg font-semibold">Total Stations</div>
                            </div>
                            <div class="flex-1 flex items-center">
                                <div class="text-3xl font-bold text-[#322f90]">
                                    {{ $response->stations ?? session('dashboard_stations') ?? 0 }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Total Accounts -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-[#322f90] h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-[#322f90] text-lg font-semibold">Total Accounts</div>
                        </div>
                        <div class="flex-1 flex items-center">
                            <div class="text-3xl font-bold text-[#322f90]">
                                {{ $response->accounts ?? session('dashboard_accounts') ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Accounts -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-green-500 h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-green-600 text-lg font-semibold">Active Accounts</div>
                        </div>
                        <div class="flex-1 flex items-center">
                            <div class="text-3xl font-bold text-green-600">
                                {{ $response->activeAccounts ?? session('dashboard_activeAccounts') ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blocked Accounts -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-[#d1111b] h-full flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <div class="text-[#d1111b] text-lg font-semibold">Blocked Accounts</div>
                        </div>
                        <div class="flex-1 flex items-center">
                            <div class="text-3xl font-bold text-[#d1111b]">
                                {{ $response->blockedAccounts ?? session('dashboard_blockedAccounts') ?? 0 }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Drivers -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <a href="{{ route('drivers.index') }}" class="block h-full">
                        <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-blue-500 hover:shadow-xl transition-shadow duration-200 h-full flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-blue-600 text-lg font-semibold">Total Drivers</div>
                            </div>
                            <div class="flex-1 flex items-center">
                                <div class="text-3xl font-bold text-blue-600">
                                    @php
                                        $driversCount = $response->drivers ?? session('dashboard_drivers') ?? 0;
                                        $driversCount = is_numeric($driversCount) ? (int) $driversCount : 0;
                                    @endphp
                                    {{ $driversCount }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Regions -->
                <div class="col-span-12 sm:col-span-6 xl:col-span-4">
                    <a href="{{ route('regions.index') }}" class="block h-full">
                        <div class="bg-white shadow-lg rounded-lg p-5 border-b-4 border-purple-500 hover:shadow-xl transition-shadow duration-200 h-full flex flex-col">
                            <div class="flex items-center justify-between mb-3">
                                <div class="text-purple-600 text-lg font-semibold">Total Regions</div>
                            </div>
                            <div class="flex-1 flex items-center">
                                <div class="text-3xl font-bold text-purple-600">
                                    @php
                                        $regionsCount = $response->regions ?? session('dashboard_regions') ?? 0;
                                        $regionsCount = is_numeric($regionsCount) ? (int) $regionsCount : 0;
                                    @endphp
                                    {{ $regionsCount }}
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                function updateDashboardDateTime() {
                    const now = new Date();
                    const dateEl = document.getElementById('currentDate');
                    const timeEl = document.getElementById('currentTime');
                    if (!dateEl || !timeEl) return;

                    const options = {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    };
                    dateEl.textContent = now.toLocaleDateString('en-US', options);

                    timeEl.textContent = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                    });
                }

                updateDashboardDateTime();
                setInterval(updateDashboardDateTime, 1000);
            </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>

