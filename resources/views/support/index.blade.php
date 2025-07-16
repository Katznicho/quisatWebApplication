<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-10">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100">Support Center</h2>
            <p class="mt-2 text-base text-gray-600 dark:text-gray-400">Reach out to us for help with your trading bot or account. We're here to help you succeed.</p>
        </div>

        <!-- Quick Support Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
            <!-- Telegram -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-[#011478]/10 rounded-full">
                        <svg class="h-6 w-6 text-[#011478]" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9.15 17.56L9.8 21c.6 0 .86-.25 1.18-.55l2.83-2.7 5.87 4.28c1.08.63 1.86.3 2.17-.96L24 3.46c.29-1.28-.45-1.78-1.5-1.41L.57 9.64C-.75 10.13-.75 10.9.57 11.3l6.21 2.07 2.37 7.1z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Telegram</h3>
                        <a href="https://t.me/nextgentradenetwork" target="_blank" class="mt-2 text-sm text-[#011478] hover:underline">Join Chat</a>
                    </div>
                </div>
            </div>

            <!-- WhatsApp -->
            <div class="bg-white dark:bg-gray-800 shadow-xl rounded-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-[#011478]/10 rounded-full">
                        <svg class="h-6 w-6 text-[#011478]" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="..."/> <!-- WhatsApp SVG path as you already have -->
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">WhatsApp</h3>
                        <a href="https://wa.me/256750873575" target="_blank" class="mt-2 text-sm text-[#011478] hover:underline">+256 750 873575</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mb-12">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Frequently Asked Questions</h3>
            <ul class="space-y-4 text-gray-700 dark:text-gray-300">
                <li>
                    <strong>How do I set up my trading bot?</strong>
                    <p class="text-sm mt-1">Visit the setup guide in the documentation or reach out via Telegram for live assistance.</p>
                </li>
                <li>
                    <strong>Where can I check my account status?</strong>
                    <p class="text-sm mt-1">You can check your account dashboard for full details and settings.</p>
                </li>
                <li>
                    <strong>Is there a support response time?</strong>
                    <p class="text-sm mt-1">We usually respond within 1–3 hours on weekdays.</p>
                </li>
            </ul>
        </div>

        <!-- Feedback/Suggestion Form -->
        <div class="bg-gray-50 dark:bg-gray-900 shadow-inner rounded-lg p-6">
            <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">Send Us a Message</h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Have suggestions or need further help? Send us a message below:</p>
            <form>
                <textarea class="w-full p-3 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-gray-100" rows="4" placeholder="Write your message or feedback here..."></textarea>
                <button type="submit" class="mt-4 px-4 py-2 bg-[#011478] text-white rounded hover:bg-[#02178a]">Send Message</button>
            </form>
        </div>
    </div>
</x-app-layout>
