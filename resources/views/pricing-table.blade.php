@php
    // Get packages for the table, ordered by sort_order
    $tablePackages = $packages ?? collect([]);
@endphp

@if($tablePackages->count() > 0)
<div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-200">
    <div class="bg-primary-600 px-6 py-4">
        <h3 class="text-xl font-bold text-white text-center">SMS Pricing</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-100">
                    <th class="px-6 py-4 text-left text-sm font-bold text-gray-900 border-b-2 border-gray-300">NETWORK</th>
                    @foreach($tablePackages as $pkg)
                    <th class="px-6 py-4 text-center text-sm font-bold text-gray-900 border-b-2 border-gray-300">{{ strtoupper($pkg->name) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-5 text-sm font-semibold text-gray-900">All networks</td>
                    @foreach($tablePackages as $pkg)
                    <td class="px-6 py-5 text-center">
                        <div>
                            <span class="text-3xl font-bold text-primary-700">{{ number_format((float)($pkg->cost_per_sms ?? 30.00), 0) }}</span>
                            <span class="text-sm text-gray-600 ml-1">UGX</span>
                        </div>
                    </td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="px-6 py-4 bg-gray-50 border-t-2 border-gray-200">
        <p class="text-xs text-gray-600 text-center">
            <span class="font-semibold">Note:</span> Default rate for businesses without a package is 30 UGX per SMS. 
            @if($tablePackages->count() > 0)
            Package rates 
            @php
                $rateList = [];
                foreach($tablePackages as $pkg) {
                    $smsCost = $pkg->cost_per_sms;
                    if (is_null($smsCost) || $smsCost === '') {
                        $smsCost = 30;
                    }
                    $rateList[] = number_format((float)$smsCost, 0) . ' UGX for ' . $pkg->name;
                }
            @endphp
            {{ implode(', ', $rateList) }}
            @endif
             apply when subscribed. Packages are free - no payment required.
        </p>
    </div>
</div>
@else
<div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-200 p-8 text-center">
    <p class="text-gray-600">Packages are being configured. Default rate is 30 UGX per SMS.</p>
</div>
@endif

