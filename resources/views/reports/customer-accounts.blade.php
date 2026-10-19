<x-app-layout title="Customer Accounts - Stabex Customer Portal">
    <x-dashboard-layout header="Customer Accounts">
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full">
            <div class="w-full max-w-4xl mx-auto">
                <h1 class="text-3xl font-bold text-[#322f90] mb-2">Customer Accounts</h1>
                <p class="text-gray-600 mb-6">Download your customer accounts report</p>

                <div class="bg-white shadow-lg rounded-2xl p-6 sm:p-8 border-b-4 border-[#322f90]">
                    <form id="customerAccountsForm">
                        @csrf
                        <div class="flex justify-center">
                            <button type="submit"
                                    id="downloadBtn"
                                    class="inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-lg shadow-sm text-base font-medium text-white bg-[#322f90] hover:bg-[#d1111b] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#322f90] transition-colors duration-150">
                                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Download Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
(function () {
    var downloadUrl = @json(route('customer-accounts.store'));
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    document.getElementById('customerAccountsForm').addEventListener('submit', function (e) {
        e.preventDefault();

        var btn = document.getElementById('downloadBtn');
        btn.disabled = true;

        Swal.fire({
            title: 'Downloading Report...',
            html: '<p>Please wait while your report is being prepared.</p><p class="text-sm text-gray-500 mt-2">Please do not close this window.</p>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: function () { Swal.showLoading(); }
        });

        var formData = new FormData();
        formData.append('_token', csrfToken);

        fetch(downloadUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: formData
        }).then(function (resp) {
            return resp.json().catch(function () { return { success: false, message: 'Invalid response from server.' }; });
        }).then(function (data) {
            Swal.close();
            btn.disabled = false;

            if (!data.success) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to download report.',
                    confirmButtonColor: '#d1111b'
                });
                return;
            }

            if (!data.pdf) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No PDF data received from server.',
                    confirmButtonColor: '#d1111b'
                });
                return;
            }

            // Decode base64 and download
            var bstr = atob(data.pdf);
            var n = bstr.length;
            var u8arr = new Uint8Array(n);
            while (n--) {
                u8arr[n] = bstr.charCodeAt(n);
            }
            var blob = new Blob([u8arr], { type: 'application/pdf' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'CustomerAccounts_' + new Date().getTime() + '.pdf';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Your report has been downloaded successfully.',
                confirmButtonColor: '#322f90'
            });
        }).catch(function (error) {
            Swal.close();
            btn.disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Network error: ' + error.message,
                confirmButtonColor: '#d1111b'
            });
        });
    });
})();
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>
