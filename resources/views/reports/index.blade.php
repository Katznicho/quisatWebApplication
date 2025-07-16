<x-app-layout>
    <div class="max-w-7xl mx-auto py-12 px-6 space-y-10">
        <h1 class="text-3xl font-bold text-gray-800">Trading Reports</h1>

        <div class="bg-white border-l-4 border-blue-600 shadow rounded-xl p-6">
            <h2 class="text-2xl font-semibold text-blue-600 mb-2">📊 Coming Soon: Advanced Performance Reports</h2>
            <p class="text-gray-700 mb-4">We are building powerful insights to help you optimize your trades like a pro. Get ready for:</p>

            <ul class="list-disc list-inside text-gray-800 space-y-2">
                <li><strong>Daily Profit & Loss Reports</strong> – Know exactly how your bot is performing each day.</li>
                <li><strong>Trade Accuracy Metrics</strong> – Measure how many trades closed in profit.</li>
                <li><strong>Bot Performance Comparison</strong> – See which signals or strategies are most effective.</li>
                <li><strong>Risk-to-Reward Analysis</strong> – Understand how well risk is being managed.</li>
                <li><strong>Best Trading Times</strong> – Discover when your bot performs best (by hour/day).</li>
            </ul>

            <div class="mt-6 text-sm text-gray-600 italic">
                Our reports will be available soon for all Pro and Enterprise users.
                Upgrade your plan to unlock advanced analytics and gain the edge.
            </div>

            <div class="mt-6">
                <a href="{{ route('subscriptions.index') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-lg shadow">
                    Upgrade Now to Unlock
                </a>
            </div>
        </div>

        <div class="text-center text-gray-500 italic">
            🚀 We're committed to helping you trade smarter, not harder.
        </div>
    </div>
</x-app-layout>
