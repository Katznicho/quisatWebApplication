<div class="space-y-6">
    
    <!-- Error Messages -->
    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif
    
    <!-- Filters Section -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Category Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                <select wire:model="selectedCategory" id="category" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category }}">{{ $category }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Country Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Country</label>
                <select wire:model="selectedCountry" id="country" class="w-full rounded-lg border-blue-300 dark:border-blue-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 bg-blue-50 dark:bg-blue-900/20">
                    <option value="">All Countries</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}">{{ $country }}</option>
                    @endforeach
                </select>
            </div>

            <!-- District/State Filter -->
            <div class="flex-1 min-w-[200px]">
                <label for="district" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">District/State</label>
                <select wire:model="selectedDistrict" id="district" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Districts/States</option>
                    @foreach($districts as $district)
                        <option value="{{ $district }}">{{ $district }}</option>
                    @endforeach
                </select>
            </div>

            <!-- From Date -->
            <div class="flex-1 min-w-[150px]">
                <label for="from_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From</label>
                <div class="relative">
                    <input type="text" wire:model="fromDate" id="from_date" placeholder="dd/mm/yyyy" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 pr-10">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- To Date -->
            <div class="flex-1 min-w-[150px]">
                <label for="to_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">To</label>
                <div class="relative">
                    <input type="text" wire:model="toDate" id="to_date" placeholder="dd/mm/yyyy" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 pr-10">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Apply Filters Button -->
            <div class="flex items-end gap-2">
                <button wire:click="applyFilters" wire:loading.attr="disabled" class="bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-6 py-2 rounded-lg font-medium transition-colors duration-200">
                    <span wire:loading.remove>Apply Filters</span>
                    <span wire:loading>Loading...</span>
                </button>
                <button wire:click="resetFilters" wire:loading.attr="disabled" class="bg-gray-500 hover:bg-gray-600 disabled:bg-gray-400 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Data Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Total Businesses Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">TOTAL BUSINESSES</h3>
            </div>
            <div class="flex items-baseline mb-2">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    {{ number_format($totalBusinesses) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $businessesChange }}% from last month
            </div>
        </div>

        <!-- Total Users Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">TOTAL USERS</h3>
            </div>
            <div class="flex items-baseline mb-2">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    {{ number_format($totalUsers) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $usersChange }}% from last month
            </div>
        </div>

        <!-- Active Business Clients Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">ACTIVE BUSINESS CLIENTS</h3>
            </div>
            <div class="flex items-baseline mb-2">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    {{ number_format($activeBusinessClients) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $clientsChange }}% from last month
            </div>
        </div>

        <!-- Active Business Staff Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">ACTIVE BUSINESS STAFF</h3>
            </div>
            <div class="flex items-baseline mb-2">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    {{ number_format($activeBusinessStaff) }}
                </span>
            </div>
            <div class="flex items-center text-green-600 text-sm">
                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd"></path>
                </svg>
                ↑ {{ $staffChange }}% from last month
            </div>
        </div>

    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- New Users Line Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">New Users</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="newUsersChart" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- User Distribution Pie Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">User Distribution</h3>
            <div class="relative" style="height: 300px;">
                <canvas id="userDistributionChart" width="400" height="300"></canvas>
            </div>
        </div>

    </div>

    <!-- System Health and User Role Distribution Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- System Health -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">System Health</h3>
            <div class="space-y-4">
                <!-- Server Status -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">Server Status</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['server']['status'] ?? 'Online' }} ({{ $systemHealth['server']['uptime'] ?? '99.98%' }} uptime)</span>
                </div>

                <!-- Database -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">Database</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['database']['status'] ?? 'Operational' }}</span>
                </div>

                <!-- Storage -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">Storage</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['storage']['status'] ?? '78%' }} ({{ $systemHealth['storage']['warning'] ?? 'Warning' }})</span>
                </div>

                <!-- API Services -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-700 dark:text-gray-300">API Services</span>
                    </div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $systemHealth['api_services']['status'] ?? 'All Operational' }}</span>
                </div>
            </div>
        </div>

        <!-- User Role Distribution -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">User Role Distribution</h3>
            <div class="relative" style="height: 200px;">
                <canvas id="userRoleDistributionChart" width="400" height="200"></canvas>
            </div>
        </div>

    </div>

</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // New Users Line Chart
    const newUsersCtx = document.getElementById('newUsersChart').getContext('2d');
    const newUsersChart = new Chart(newUsersCtx, {
        type: 'line',
        data: {
            labels: @json($newUsersChartData['labels'] ?? []),
            datasets: [{
                label: 'New Users',
                data: @json($newUsersChartData['data'] ?? []),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        stepSize: 500,
                        maxTicksLimit: 10
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            elements: {
                point: {
                    hoverBackgroundColor: '#3B82F6'
                }
            }
        }
    });

    // User Distribution Pie Chart
    const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
    const userDistributionChart = new Chart(userDistributionCtx, {
        type: 'pie',
        data: {
            labels: @json($userDistributionChartData['labels'] ?? []),
            datasets: [{
                data: @json($userDistributionChartData['data'] ?? []),
                backgroundColor: @json($userDistributionChartData['colors'] ?? []),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });

    // User Role Distribution Pie Chart
    const userRoleDistributionCtx = document.getElementById('userRoleDistributionChart').getContext('2d');
    const userRoleDistributionChart = new Chart(userRoleDistributionCtx, {
        type: 'pie',
        data: {
            labels: @json($userRoleDistributionData['labels'] ?? []),
            datasets: [{
                data: @json($userRoleDistributionData['data'] ?? []),
                backgroundColor: @json($userRoleDistributionData['colors'] ?? []),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Update charts when Livewire updates
    window.addEventListener('livewire:load', function () {
        Livewire.on('chartsUpdated', function () {
            // Update line chart
            newUsersChart.data.labels = @json($newUsersChartData['labels'] ?? []);
            newUsersChart.data.datasets[0].data = @json($newUsersChartData['data'] ?? []);
            newUsersChart.update();

            // Update pie chart
            userDistributionChart.data.labels = @json($userDistributionChartData['labels'] ?? []);
            userDistributionChart.data.datasets[0].data = @json($userDistributionChartData['data'] ?? []);
            userDistributionChart.data.datasets[0].backgroundColor = @json($userDistributionChartData['colors'] ?? []);
            userDistributionChart.update();

            // Update user role distribution chart
            userRoleDistributionChart.data.labels = @json($userRoleDistributionData['labels'] ?? []);
            userRoleDistributionChart.data.datasets[0].data = @json($userRoleDistributionData['data'] ?? []);
            userRoleDistributionChart.data.datasets[0].backgroundColor = @json($userRoleDistributionData['colors'] ?? []);
            userRoleDistributionChart.update();
        });
    });
</script>
