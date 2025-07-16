<div class="space-y-6">
    
    <!-- Account Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        
        <!-- Account Balance Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Account Balance</h3>
                <span class="text-xs text-gray-400">Last Update: 2025-06-23 10:00 AM</span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    {{ number_format($balance, 2) }}
                </span>
                <span class="ml-2 text-sm text-gray-500">UGX</span>
            </div>
        </div>

        <!-- Total Transactions Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Transactions</h3>
                <span class="text-xs text-gray-400">All Time</span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    1,234
                </span>
                <span class="ml-2 text-sm text-gray-500">Txns</span>
            </div>
        </div>

        <!-- Approved Withdrawals Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Approved Withdrawals</h3>
                <span class="text-xs text-gray-400">Total</span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-bold text-[#011478] dark:text-white">
                    1,500,000
                </span>
                <span class="ml-2 text-sm text-gray-500">UGX</span>
            </div>
        </div>

        <!-- Pending Payments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Pending Payments</h3>
                <span class="text-xs text-gray-400">Review Required</span>
            </div>
            <div class="flex items-baseline">
                <span class="text-2xl font-bold text-orange-500 dark:text-yellow-400">
                    300,000
                </span>
                <span class="ml-2 text-sm text-gray-500">UGX</span>
            </div>
        </div>

    </div>

</div>
