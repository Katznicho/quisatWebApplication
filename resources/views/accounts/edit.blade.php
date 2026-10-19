<x-app-layout title="{{ (isset($readonly) && $readonly) ? 'View Account' : 'Edit Account' }} - Stabex Customer Portal">
    <x-dashboard-layout header="{{ (isset($readonly) && $readonly) ? 'View Account' : 'Edit Account' }}">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-lg font-semibold text-[#322f90]">{{ isset($readonly) && $readonly ? 'View Account' : 'Edit Account' }}</h1>

                <div class="flex gap-3">
                @if(isset($readonly) && $readonly)
                    <a href="{{ route('accounts.edit', ['account' => $account->Account_No]) }}"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#322f90] hover:bg-[#252171] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90]">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Account
                    </a>
                @else
                @if($account->Blocked)
                    <button onclick="confirmUnblock()"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#28a745] hover:bg-[#218838] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#28a745]">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Unblock Account
                    </button>
                @else
                    <button onclick="confirmBlock()"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-[#d1111b] hover:bg-[#b30f18] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#d1111b]">
                        <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Block Account
                    </button>
                @endif
                @endif
                <a href="{{ route('accounts.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90]">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Accounts
                </a>
                </div>
            </div>

            <div class="bg-white shadow-lg sm:rounded-2xl border border-gray-100">
                <div class="px-6 py-5 border-b border-gray-100 grid grid-cols-1 lg:grid-cols-[minmax(0,18rem)_minmax(0,1fr)] gap-6 items-stretch">
                    <div class="min-w-0 flex flex-col justify-center">
                        <p class="text-xs font-semibold tracking-[0.16em] text-gray-500 uppercase">Account Management</p>
                        <h3 class="mt-1 text-xl font-semibold text-[#322f90] truncate">
                            {{ $account->Account_No }}
                            @if($account->Account_Name)
                                <span class="ml-2 text-sm font-normal text-gray-500">· {{ $account->Account_Name }}</span>
                            @endif
                        </h3>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium {{ $account->Blocked ? 'bg-red-50 text-red-700 ring-1 ring-red-100' : 'bg-green-50 text-green-700 ring-1 ring-green-100' }}">
                                <span class="h-2 w-2 rounded-full mr-2 {{ $account->Blocked ? 'bg-red-500' : 'bg-green-500' }}"></span>
                                {{ $account->Blocked ? 'Blocked' : 'Active' }}
                            </span>
                            <span class="text-xs text-gray-400">
                                Customer No: {{ $account->Customer_No ?? session('customerNumber') }}
                            </span>
                        </div>
                    </div>
                    @include('accounts.partials.speedometer')
                </div>

                <div class="p-6 sm:p-8">
                    <form id="editAccountForm">
                        @csrf
                        <div class="grid grid-cols-1 gap-y-6 gap-x-8 md:grid-cols-2">
                            <input type="hidden" name="patch_key" id="patch_key" value="{{ $account->Patchkey ?? $account->PatchKey ?? '' }}" />

                            <div>
                                <label for="account_no" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Account No</label>
                                <input
                                    type="text"
                                    name="account_no"
                                    id="account_no"
                                    value="{{ $account->Account_No }}"
                                    readonly
                                    class="mt-1 block w-full h-10 px-3 sm:text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 cursor-default focus:outline-none focus:ring-0"
                                >
                            </div>

                            <div>
                                <label for="card_no" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Card Number</label>
                                <input
                                    type="text"
                                    name="card_no"
                                    id="card_no"
                                    value="{{ $account->Card_No ?? '' }}"
                                    readonly
                                    class="mt-1 block w-full h-10 px-3 sm:text-sm border border-gray-200 rounded-lg bg-gray-50 text-gray-700 cursor-default focus:outline-none focus:ring-0"
                                >
                            </div>

                            <div>
                                <label for="account_name" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Account Name</label>
                                <input
                                    type="text"
                                    name="account_name"
                                    id="account_name"
                                    value="{{ $account->Account_Name ?? '' }}"
                                    {{ (isset($readonly) && $readonly) ? 'readonly' : '' }}
                                    class="mt-1 block w-full h-10 px-3 sm:text-sm border border-gray-200 rounded-lg focus:ring-[#322f90] focus:border-[#322f90] {{ (isset($readonly) && $readonly) ? 'bg-gray-50 text-gray-500 cursor-default' : 'bg-white' }}"
                                >
                            </div>

                            <div>
                                <label for="phone_no" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Phone Number</label>
                                <input
                                    type="text"
                                    name="phone_no"
                                    id="phone_no"
                                    value="{{ $account->Phone_No ?? '' }}"
                                    {{ (isset($readonly) && $readonly) ? 'readonly' : '' }}
                                    class="mt-1 block w-full h-10 px-3 sm:text-sm border border-gray-200 rounded-lg focus:ring-[#322f90] focus:border-[#322f90] {{ (isset($readonly) && $readonly) ? 'bg-gray-50 text-gray-500 cursor-default' : 'bg-white' }}"
                                >
                            </div>

                            <div>
                                <label for="email" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Email</label>
                                <input
                                    type="email"
                                    name="email"
                                    id="email"
                                    value="{{ $account->Email ?? '' }}"
                                    {{ (isset($readonly) && $readonly) ? 'readonly' : '' }}
                                    class="mt-1 block w-full h-10 px-3 sm:text-sm border border-gray-200 rounded-lg focus:ring-[#322f90] focus:border-[#322f90] {{ (isset($readonly) && $readonly) ? 'bg-gray-50 text-gray-500 cursor-default' : 'bg-white' }}"
                                >
                            </div>

                            <div>
                                <label for="vehicle_reg_no" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Vehicle Reg No</label>
                                <input
                                    type="text"
                                    name="vehicle_reg_no"
                                    id="vehicle_reg_no"
                                    value="{{ $account->Vehicle_Reg_No ?? '' }}"
                                    {{ (isset($readonly) && $readonly) ? 'readonly' : '' }}
                                    class="mt-1 block w-full h-10 px-3 sm:text-sm border border-gray-200 rounded-lg focus:ring-[#322f90] focus:border-[#322f90] {{ (isset($readonly) && $readonly) ? 'bg-gray-50 text-gray-500 cursor-default' : 'bg-white' }}"
                                >
                            </div>

                            @php
                                $hasBalance = property_exists($account, 'Balance') || isset($account->Balance);
                                $hasCeilingPeriod = property_exists($account, 'Ceiling_Period') || isset($account->Ceiling_Period);
                                $hasUsageCeiling = property_exists($account, 'Usage_Ceiling') || isset($account->Usage_Ceiling);
                                $accountType = $account->Account_Type ?? $account->AccountType ?? null;
                                $isBalanceAccount = false;
                                $isCeilingAccount = false;
                                if ($accountType) {
                                    $accountTypeLower = strtolower($accountType);
                                    if (strpos($accountTypeLower, 'balance') !== false) $isBalanceAccount = true;
                                    elseif (strpos($accountTypeLower, 'ceiling') !== false || strpos($accountTypeLower, 'limit') !== false) $isCeilingAccount = true;
                                } else {
                                    if ($hasBalance) $isBalanceAccount = true;
                                    elseif ($hasCeilingPeriod || $hasUsageCeiling) $isCeilingAccount = true;
                                }
                            @endphp

                            @if($isBalanceAccount && $hasBalance)
                                <div>
                                    <label for="balance" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Balance</label>
                                    <input
                                        type="text"
                                        name="balance"
                                        id="balance"
                                        value="{{ number_format($account->Balance ?? 0, 2) }}"
                                        disabled
                                        class="mt-1 block w-full h-10 px-3 sm:text-sm border-0 rounded-full bg-gray-100 text-gray-700 cursor-default focus:outline-none focus:ring-0"
                                    >
                                </div>
                            @elseif($isCeilingAccount)
                                @if($hasCeilingPeriod)
                                <div>
                                    <label for="ceiling_period" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Ceiling Period</label>
                                    <input
                                        type="text"
                                        name="ceiling_period"
                                        id="ceiling_period"
                                        value="{{ $account->Ceiling_Period ?? '' }}"
                                        disabled
                                        class="mt-1 block w-full h-10 px-3 sm:text-sm border-0 rounded-full bg-gray-100 text-gray-700 cursor-default focus:outline-none focus:ring-0"
                                    >
                                </div>
                                @endif
                                @if($hasUsageCeiling)
                                <div>
                                    <label for="usage_ceiling" class="block text-xs font-semibold tracking-wide text-gray-500 uppercase">Usage Ceiling</label>
                                    <input
                                        type="text"
                                        name="usage_ceiling"
                                        id="usage_ceiling"
                                        value="{{ number_format($account->Usage_Ceiling ?? 0, 2) }}"
                                        disabled
                                        class="mt-1 block w-full h-10 px-3 sm:text-sm border-0 rounded-full bg-gray-100 text-gray-700 cursor-default focus:outline-none focus:ring-0"
                                    >
                                </div>
                                @endif
                            @endif
                        </div>
                    </form>

                    <div class="mt-8 pt-6 border-t border-gray-200"
                         x-data="{ activeTab: '{{ ($cardLocations && count($cardLocations) > 0) ? 'locations' : 'limits' }}' }"
                         x-init="
                        $watch('activeTab', value => {
                            setTimeout(() => {
                                if (value === 'locations' && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#cardLocationsTable')) {
                                    $('#cardLocationsTable').DataTable().columns.adjust();
                                }
                                if (value === 'limits' && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#cardLimitsTable')) {
                                    $('#cardLimitsTable').DataTable().columns.adjust();
                                }
                                if (value === 'usage' && typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#usageDaysTable')) {
                                    $('#usageDaysTable').DataTable().columns.adjust();
                                }
                            }, 100);
                        });
                    ">
                        <x-filament::tabs label="Account Details">
                            @if($cardLocations && count($cardLocations) > 0)
                                <x-filament::tabs.item alpine-active="activeTab === 'locations'" x-on:click="activeTab = 'locations'">Card Locations</x-filament::tabs.item>
                            @endif
                            <x-filament::tabs.item alpine-active="activeTab === 'limits'" x-on:click="activeTab = 'limits'">Card Limits</x-filament::tabs.item>
                            <x-filament::tabs.item alpine-active="activeTab === 'usage'" x-on:click="activeTab = 'usage'">Usage Days</x-filament::tabs.item>
                        </x-filament::tabs>

                        <div class="mt-6">
                            @if($cardLocations && count($cardLocations) > 0)
                            <div x-show="activeTab === 'locations'" x-cloak>
                                <div class="overflow-x-auto">
                                    <table id="cardLocationsTable" class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location Name</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($cardLocations as $location)
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $location->Location_Code ?? '' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $location->Location_Name ?? '' }}</td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if(isset($readonly) && $readonly)
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full {{ ($location->Active ?? false) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                            {{ ($location->Active ?? false) ? 'Yes' : 'No' }}
                                                        </span>
                                                    @else
                                                        <input type="checkbox" class="h-4 w-4 text-[#322f90] focus:ring-[#322f90] border-gray-300 rounded"
                                                            {{ ($location->Active ?? false) ? 'checked' : '' }}
                                                            data-patchkey="{{ $location->PatchKey ?? $location->Patchkey ?? '' }}"
                                                            data-field="Active" data-type="boolean"
                                                            onchange="updateCardLocationField(this)">
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            <div x-show="activeTab === 'limits'" x-cloak>
                                <div class="overflow-x-auto">
                                    <table id="cardLimitsTable" class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item No</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Description</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Period</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Limit Quantity</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity Consumed</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cardLimitsBody" class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td class="px-6 py-6 text-center text-sm text-gray-400">Loading card limits...</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div x-show="activeTab === 'usage'" x-cloak>
                                <div class="overflow-x-auto">
                                    <table id="usageDaysTable" class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                                            </tr>
                                        </thead>
                                        <tbody id="usageDaysBody" class="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td class="px-6 py-6 text-center text-sm text-gray-400">Loading usage days...</td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                    </div>
        </div>

        @push('scripts')
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script>
        (function() {
            var urls = {
                updateField: @json(route('accounts.update-field')),
                blockAccount: @json(route('accounts.block')),
                updateCardLocation: @json(route('accounts.update-card-location')),
                updateUsageDay: @json(route('accounts.update-usage-day')),
                fetchCardLimits: @json(route('accounts.card-limits', ['account' => $account->Account_No])),
                fetchUsageDays: @json(route('accounts.usage-days', ['account' => $account->Account_No]))
            };

            document.addEventListener('DOMContentLoaded', function() {
                var accountNo = document.getElementById('account_no') ? document.getElementById('account_no').value : '';
                var customerNo = '{{ $account->Customer_No ?? session('customerNumber') }}';
                var cardNo = '{{ $account->Card_No ?? '' }}';

                function renderCardLimits(rows) {
                    var tbody = document.getElementById('cardLimitsBody');
                    if (!tbody) return;
                    if (!rows || !rows.length) {
                        tbody.innerHTML =
                            '<tr>' +
                                '<td class=\"px-6 py-6 text-center text-sm text-gray-400\">No card limits found for this account.</td>' +
                                '<td></td><td></td><td></td><td></td><td></td><td></td>' +
                            '</tr>';
                        return;
                    }
                    tbody.innerHTML = rows.map(function(limit) {
                        return '<tr>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.Item_No || '') + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.Item_Description || '') + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.Period || '') + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.Limit_Quantity || '') + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.Quantity_Consumed || '') + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.Start_Date || '') + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900 bg-gray-50\">' + (limit.End_Date || '') + '</td>' +
                        '</tr>';
                    }).join('');
                }

                function renderUsageDays(rows) {
                    var tbody = document.getElementById('usageDaysBody');
                    if (!tbody) return;
                    if (!rows || !rows.length) {
                        tbody.innerHTML =
                            '<tr>' +
                                '<td class=\"px-6 py-6 text-center text-sm text-gray-400\">No usage days configured for this account.</td>' +
                                '<td></td><td></td><td></td>' +
                            '</tr>';
                        return;
                    }
                    var readonly = {{ (isset($readonly) && $readonly) ? 'true' : 'false' }};
                    tbody.innerHTML = rows.map(function(row) {
                        var patchKey = row.patchKey || row.PatchKey || '';
                        var day = row.day || '';
                        var startTimeCell = readonly
                            ? '<span class=\"text-gray-900\">' + (row.start_Time || '') + '</span>'
                            : '<input type=\"time\" value=\"' + (row.start_Time || '') + '\" class=\"mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-[#322f90] focus:border-[#322f90]\" data-patchkey=\"' + patchKey + '\" data-day=\"' + day + '\" data-field=\"start_Time\" data-type=\"text\" onchange=\"updateUsageDayField(this)\">';
                        var endTimeCell = readonly
                            ? '<span class=\"text-gray-900\">' + (row.end_Time || '') + '</span>'
                            : '<input type=\"time\" value=\"' + (row.end_Time || '') + '\" class=\"mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md focus:ring-[#322f90] focus:border-[#322f90]\" data-patchkey=\"' + patchKey + '\" data-day=\"' + day + '\" data-field=\"end_Time\" data-type=\"text\" onchange=\"updateUsageDayField(this)\">';
                        var activeCell = readonly
                            ? '<span class=\"px-2 py-1 text-xs font-semibold rounded-full ' + ((row.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) + '\">' + (row.active ? 'Yes' : 'No') + '</span>'
                            : '<input type=\"checkbox\" class=\"h-4 w-4 text-[#322f90] focus:ring-[#322f90] border-gray-300 rounded\" ' + (row.active ? 'checked' : '') + ' data-patchkey=\"' + patchKey + '\" data-day=\"' + day + '\" data-field=\"active\" data-type=\"boolean\" onchange=\"updateUsageDayField(this)\">';

                        return '<tr>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">' + day + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm\">' + startTimeCell + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm\">' + endTimeCell + '</td>' +
                            '<td class=\"px-6 py-4 whitespace-nowrap text-sm\">' + activeCell + '</td>' +
                        '</tr>';
                    }).join('');
                }

                function fetchCardLimits() {
                    var url = urls.fetchCardLimits + '?customerNo=' + encodeURIComponent(customerNo || '') + '&cardNo=' + encodeURIComponent(cardNo || '');
                    fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            console.log('fetchCardLimits response', data);
                            if (!data.success) {
                                renderCardLimits([]);
                                return;
                            }
                            renderCardLimits(data.data || []);
                        })
                        .catch(function(err) {
                            console.error('fetchCardLimits error', err);
                            renderCardLimits([]);
                        });
                }

                function fetchUsageDays() {
                    var url = urls.fetchUsageDays;
                    fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            console.log('fetchUsageDays response', data);
                            if (!data.success) {
                                renderUsageDays([]);
                                return;
                            }
                            renderUsageDays(data.data || []);
                        })
                        .catch(function(err) {
                            console.error('fetchUsageDays error', err);
                            renderUsageDays([]);
                        });
                }

                // Initial DataTables + AJAX data fetch
                setTimeout(function() {
                    @if($cardLocations && count($cardLocations) > 0)
                    if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#cardLocationsTable')) {
                        $('#cardLocationsTable').DataTable().destroy();
                    }
                    if (typeof $ !== 'undefined' && $('#cardLocationsTable').length) {
                        $('#cardLocationsTable').DataTable({ pageLength: 10, lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]], order: [[0, 'asc']] });
                    }
                    @endif
                    if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#cardLimitsTable')) {
                        $('#cardLimitsTable').DataTable().destroy();
                    }
                    if (typeof $ !== 'undefined' && $('#cardLimitsTable').length) {
                        $('#cardLimitsTable').DataTable({ pageLength: 10, lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]], order: [[0, 'asc']] });
                    }
                    if (typeof $ !== 'undefined' && $.fn.DataTable && $.fn.DataTable.isDataTable('#usageDaysTable')) {
                        $('#usageDaysTable').DataTable().destroy();
                    }
                    if (typeof $ !== 'undefined' && $('#usageDaysTable').length) {
                        $('#usageDaysTable').DataTable({ pageLength: 10, lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]], order: [[0, 'asc']] });
                    }

                    fetchCardLimits();
                    fetchUsageDays();
                }, 300);

                @if(!(isset($readonly) && $readonly))
                var patchKeyEl = document.getElementById('patch_key');
                var patchKey = patchKeyEl ? patchKeyEl.value : '';

                function updateField(fieldName, fieldValue, fieldType) {
                    fetch(urls.updateField, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: JSON.stringify({ patch_key: patchKey, field_name: fieldName, field_value: fieldValue, field_type: fieldType })
                    }).then(function(r) { return r.json(); }).then(function(data) {
                        console.log('updateField response', data);
                        if (data.success) {
                            Swal.fire({ title: 'Updated!', text: data.message, icon: 'success', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
                        } else {
                            throw new Error(data.message);
                        }
                    }).catch(function(error) {
                        Swal.fire({ title: 'Update Failed', text: error.message || 'Failed to update field', icon: 'error', toast: true, position: 'top-end', confirmButtonColor: '#d1111b' });
                    });
                }

                ['account_name','phone_no','email','vehicle_reg_no','card_no'].forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el && id !== 'card_no') {
                        el.addEventListener('change', function() {
                            var fieldMap = { account_name: 'Account_Name', phone_no: 'Phone_No', email: 'Email', vehicle_reg_no: 'Vehicle_Reg_No' };
                            updateField(fieldMap[id], this.value, 'text');
                        });
                    }
                    if (el && id === 'card_no') {
                        el.addEventListener('change', function() {
                            updateField('Card_No', this.value, 'text');
                        });
                    }
                });
                @endif
            });

            window.confirmBlock = function() {
                Swal.fire({
                    title: 'Block Account',
                    html: '<p class="mb-4">Are you sure you want to block this account?</p><div class="mb-4"><label for="block-reason" class="block text-sm font-medium text-gray-700 text-left mb-1">Reason for blocking</label><textarea id="block-reason" class="w-full px-3 py-2 border border-gray-300 rounded-md" rows="3" placeholder="Reason for blocking"></textarea></div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d1111b',
                    cancelButtonColor: '#322f90',
                    confirmButtonText: 'Yes, block it!',
                    preConfirm: function() {
                        var reason = document.getElementById('block-reason').value;
                        if (!reason.trim()) {
                            Swal.showValidationMessage('Please provide a reason');
                            return false;
                        }
                        return reason;
                    }
                }).then(function(result) {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Blocking...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                        fetch(urls.blockAccount, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ accountNo: document.getElementById('account_no').value, reason: result.value })
                        }).then(function(r) { return r.json(); }).then(function(data) {
                            console.log('blockAccount response', data);
                            if (data.success) {
                                Swal.fire({ title: 'Blocked!', text: 'Account blocked successfully', icon: 'success', confirmButtonColor: '#322f90' }).then(function() { window.location.reload(); });
                            } else {
                                throw new Error(data.message);
                            }
                        }).catch(function(e) {
                            Swal.fire({ title: 'Error!', text: e.message || 'Failed to block account', icon: 'error', confirmButtonColor: '#d1111b' });
                        });
                    }
                });
            };

            window.confirmUnblock = function() {
                Swal.fire({
                    title: 'Unblock Account',
                    text: 'Are you sure you want to unblock this account?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#322f90',
                    confirmButtonText: 'Yes, unblock it!'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        Swal.fire({ title: 'Unblocking...', allowOutsideClick: false, didOpen: function() { Swal.showLoading(); } });
                        var patchKey = document.getElementById('patch_key').value;
                        fetch(urls.updateField, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: JSON.stringify({ patch_key: patchKey, field_name: 'Blocked', field_value: false, field_type: 'boolean' })
                        }).then(function(r) { return r.json(); }).then(function(data) {
                            console.log('unblockAccount response', data);
                            if (data.success) {
                                Swal.fire({ title: 'Unblocked!', text: 'Account unblocked successfully', icon: 'success' }).then(function() { window.location.reload(); });
                            } else {
                                throw new Error(data.message);
                            }
                        }).catch(function(e) {
                            Swal.fire({ title: 'Error!', text: e.message || 'Failed to unblock', icon: 'error' });
                        });
                    }
                });
            };

            window.updateCardLocationField = function(element) {
                var patchKey = element.getAttribute('data-patchkey');
                var fieldName = element.getAttribute('data-field');
                var fieldType = element.getAttribute('data-type');
                var fieldValue = fieldType === 'boolean' ? element.checked : element.value;
                if (!patchKey) {
                    Swal.fire({ title: 'Error!', text: 'PatchKey not found.', icon: 'error', toast: true });
                    return;
                }
                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: function() { Swal.showLoading(); }
                });
                fetch(urls.updateCardLocation, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ patch_key: patchKey, field_name: fieldName, field_value: fieldValue, field_type: fieldType })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    console.log('updateCardLocation response', data);
                    if (data.success) {
                        Swal.fire({ title: 'Updated!', text: data.message || 'Updated', icon: 'success', toast: true, timer: 3000 });
                    } else {
                        throw new Error(data.message);
                    }
                }).catch(function(e) {
                    Swal.fire({ title: 'Update Failed', text: e.message, icon: 'error', toast: true });
                    if (fieldType === 'boolean') element.checked = !element.checked;
                });
            };

            window.updateUsageDayField = function(element) {
                var patchKey = element.getAttribute('data-patchkey');
                var day = element.getAttribute('data-day');
                var fieldName = element.getAttribute('data-field');
                var fieldType = element.getAttribute('data-type');
                var fieldValue = fieldType === 'boolean' ? element.checked : element.value;

                // NAV OData expects time values as HH:MM:SS, but the HTML time input
                // returns HH:MM. Normalize here before sending.
                if ((fieldName === 'start_Time' || fieldName === 'end_Time') && typeof fieldValue === 'string' && fieldValue.length === 5) {
                    fieldValue = fieldValue + ':00';
                }
                if (!patchKey || !day) {
                    Swal.fire({ title: 'Error!', text: 'PatchKey or Day not found.', icon: 'error', toast: true });
                    return;
                }
                // patchKey from OData already includes the composite key parts,
                // so we send it as-is without appending the day again.
                Swal.fire({
                    title: 'Updating...',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: function() { Swal.showLoading(); }
                });
                fetch(urls.updateUsageDay, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ patch_key: patchKey, field_name: fieldName, field_value: fieldValue, field_type: fieldType })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    console.log('updateUsageDay response', data);
                    if (data.success) {
                        Swal.fire({ title: 'Updated!', text: data.message || 'Updated', icon: 'success', toast: true, timer: 3000 });
                    } else {
                        throw new Error(data.message);
                    }
                }).catch(function(e) {
                    Swal.fire({ title: 'Update Failed', text: e.message, icon: 'error', toast: true });
                    if (fieldType === 'boolean') element.checked = !element.checked;
                });
            };
        })();
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>
