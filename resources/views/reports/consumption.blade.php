<x-app-layout title="Consumption Report - Stabex Customer Portal">
    <x-dashboard-layout header="Consumption Report">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full flex justify-center">
            <div class="w-full max-w-4xl">
                <h1 class="text-3xl font-bold text-[#322f90] mb-2">Consumption Report</h1>
                <p class="text-gray-600 mb-6">Request a consumption report for your account (it will be emailed to you)</p>

                <div class="bg-white shadow-lg rounded-2xl p-6 sm:p-8 border-b-4 border-[#322f90]">
                    <form id="consumptionReportForm"
                          method="POST"
                          action="{{ route('consumption-report.store') }}">
                        @csrf
                        <input type="hidden" name="email" id="email" value="{{ session('email') ?? '' }}">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end mb-6">
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
                                        class="inline-flex items-center justify-center w-full md:w-auto px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-[#322f90] hover:bg-[#d1111b] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90] transition-colors duration-150">
                                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                    Request Report
                                </button>
                            </div>
                        </div>
                    <div id="dateRangeWarning" class="hidden mb-4 p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded">
                        <div class="flex">
                        <div class="shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700" id="warningMessage">
                                    Large date ranges may take several minutes to generate. Please be patient.
                                </p>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </div>
        </div>
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
    (function() {
        var reportStoreUrl = @json(route('consumption-report.store'));
        var csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).getAttribute
            ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            : '';

        function checkDateRange() {
            var startDate = document.getElementById('start_date').value;
            var endDate = document.getElementById('end_date').value;
            var warningDiv = document.getElementById('dateRangeWarning');
            var warningMessage = document.getElementById('warningMessage');

            if (!startDate || !endDate || !warningDiv) return;

            var daysDiff = Math.ceil((new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24));

            if (daysDiff > 365) {
                warningMessage.textContent = 'Date range cannot exceed 365 days. Please select a shorter date range.';
                warningDiv.classList.remove('hidden', 'bg-yellow-50', 'border-yellow-400');
                warningDiv.classList.add('bg-red-50', 'border-red-400');
                warningMessage.classList.add('text-red-700');
                warningMessage.classList.remove('text-yellow-700');
            } else if (daysDiff > 180) {
                warningMessage.textContent = 'Large date range (' + daysDiff + ' days). Report may take 5-10 minutes. Please do not close this window.';
                warningDiv.classList.remove('hidden', 'bg-red-50', 'border-red-400');
                warningDiv.classList.add('bg-yellow-50', 'border-yellow-400');
                warningMessage.classList.remove('text-red-700');
                warningMessage.classList.add('text-yellow-700');
            } else if (daysDiff > 90) {
                warningMessage.textContent = 'Large date range (' + daysDiff + ' days). Report may take 3-5 minutes.';
                warningDiv.classList.remove('hidden', 'bg-red-50', 'border-red-400');
                warningDiv.classList.add('bg-yellow-50', 'border-yellow-400');
                warningMessage.classList.remove('text-red-700');
                warningMessage.classList.add('text-yellow-700');
            } else if (daysDiff > 30) {
                warningMessage.textContent = 'Date range (' + daysDiff + ' days). Report may take 1-3 minutes.';
                warningDiv.classList.remove('hidden', 'bg-red-50', 'border-red-400');
                warningDiv.classList.add('bg-yellow-50', 'border-yellow-400');
                warningMessage.classList.remove('text-red-700');
                warningMessage.classList.add('text-yellow-700');
            } else {
                warningDiv.classList.add('hidden');
            }
        }

        var startEl = document.getElementById('start_date');
        var endEl = document.getElementById('end_date');
        var form = document.getElementById('consumptionReportForm');

        if (startEl) startEl.addEventListener('change', checkDateRange);
        if (endEl) endEl.addEventListener('change', checkDateRange);
        checkDateRange();

        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                var startDate = document.getElementById('start_date').value;
                var endDate = document.getElementById('end_date').value;

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

                var estimatedTime = 'This may take a few moments.';
                if (daysDiff > 180) estimatedTime = 'This may take 5-10 minutes due to the large date range.';
                else if (daysDiff > 90) estimatedTime = 'This may take 3-5 minutes due to the date range.';
                else if (daysDiff > 30) estimatedTime = 'This may take 1-3 minutes.';

                Swal.fire({
                    title: 'Submitting Request...',
                    html: '<p>' + estimatedTime + '</p><p class="text-sm text-gray-500 mt-2">Please do not close this window.</p>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: function() { Swal.showLoading(); }
                });

                var formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('email', document.getElementById('email') ? document.getElementById('email').value : '');
                formData.append('start_date', startDate);
                formData.append('end_date', endDate);

                fetch(reportStoreUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: formData
                })
                .then(function(resp) {
                    return resp.json().catch(function() { return { success: false, message: 'Invalid response from server.' }; });
                })
                .then(function(data) {
                    Swal.close();
                    if (data && data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Request Submitted',
                            text: data.message || 'Report request submitted. You will receive it by email.',
                            confirmButtonColor: '#322f90'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Request Failed',
                            text: (data && data.message) ? data.message : 'Failed to submit report request. Please try again.',
                            confirmButtonColor: '#d1111b'
                        });
                    }
                })
                .catch(function() {
                    Swal.close();
                    Swal.fire({ icon: 'error', title: 'Network Error', text: 'Could not reach the server. Please try again.', confirmButtonColor: '#d1111b' });
                });
            });
        }
    })();
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>


