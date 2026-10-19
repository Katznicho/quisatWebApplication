<x-app-layout title="Consumption Summary - Stabex Customer Portal">
    <x-dashboard-layout header="Consumption Summary">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full">
            <div class="w-full max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-[#322f90] mb-2">Consumption Summary</h1>
                <p class="text-gray-600 mb-6">Generate a consumption summary PDF for your account</p>

                <div class="bg-white shadow-lg rounded-2xl p-6 sm:p-8 border-b-4 border-[#322f90]">
                    <form id="consumptionSummaryForm">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                            <div>
                                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="date"
                                       id="start_date"
                                       name="start_date"
                                       required
                                       class="form-input w-full rounded-lg border-gray-300 focus:border-[#322f90] focus:ring focus:ring-[#322f90] focus:ring-opacity-50"
                                       value="{{ date('Y-m-d', strtotime('-30 days')) }}">
                            </div>
                            <div>
                                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                <input type="date"
                                       id="end_date"
                                       name="end_date"
                                       required
                                       class="form-input w-full rounded-lg border-gray-300 focus:border-[#322f90] focus:ring focus:ring-[#322f90] focus:ring-opacity-50"
                                       value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="flex md:justify-end">
                                <button type="submit"
                                        id="summaryBtn"
                                        class="inline-flex items-center justify-center w-full md:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-[#322f90] hover:bg-[#d1111b] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90] transition-colors duration-150">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    Generate Summary
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
(function () {
    var summaryUrl = @json(route('consumption-summary.store'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    document.getElementById('consumptionSummaryForm').addEventListener('submit', function (e) {
        e.preventDefault();

        var startDate = document.getElementById('start_date').value;
        var endDate   = document.getElementById('end_date').value;

        if (!startDate || !endDate) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Please select both start and end dates.', confirmButtonColor: '#d1111b' });
            return;
        }

        if (new Date(startDate) > new Date(endDate)) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Start date must be before or equal to end date.', confirmButtonColor: '#d1111b' });
            return;
        }

        var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24));
        if (daysDiff > 365) {
            Swal.fire({ icon: 'error', title: 'Date Range Too Large', text: 'Date range cannot exceed 365 days.', confirmButtonColor: '#d1111b' });
            return;
        }

        var btn = document.getElementById('summaryBtn');
        btn.disabled = true;

        Swal.fire({
            title: 'Generating Summary...',
            html: '<p>Please wait while your summary is being prepared.</p><p class="text-sm text-gray-500 mt-2">Please do not close this window.</p>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        var formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('start_date', startDate);
        formData.append('end_date', endDate);

        fetch(summaryUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        }).then(function (resp) {
            return resp.json().catch(function () { return { success: false, message: 'Invalid response from server.' }; });
        }).then(function (data) {
            Swal.close();
            btn.disabled = false;

            if (data && data.success && data.pdf) {
                try {
                    var byteChars   = atob(data.pdf);
                    var byteNumbers = new Uint8Array(byteChars.length);
                    for (var i = 0; i < byteChars.length; i++) {
                        byteNumbers[i] = byteChars.charCodeAt(i);
                    }
                    var blob    = new Blob([byteNumbers], { type: 'application/pdf' });
                    var blobUrl = URL.createObjectURL(blob);

                    // Use a download link — avoids popup blocker on async callbacks
                    var link      = document.createElement('a');
                    link.href     = blobUrl;
                    link.download = 'consumption-summary.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    setTimeout(function () { URL.revokeObjectURL(blobUrl); }, 2000);

                    Swal.fire({
                        icon: 'success',
                        title: 'Summary Ready',
                        text: 'Your consumption summary PDF is downloading.',
                        confirmButtonColor: '#322f90'
                    });
                } catch (pdfErr) {
                    Swal.fire({ icon: 'error', title: 'PDF Error', text: 'Failed to decode PDF data: ' + pdfErr.message, confirmButtonColor: '#d1111b' });
                }
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: (data && data.message) ? data.message : 'Failed to generate summary. Please try again.', confirmButtonColor: '#d1111b' });
            }
        }).catch(function () {
            Swal.close();
            btn.disabled = false;
            Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please try again.', confirmButtonColor: '#d1111b' });
        });
    });
})();
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>
