@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    @include('marketplace._hub-tabs', ['availableHubs' => $availableHubs ?? [], 'hub' => $hub ?? 'kidz_mart', 'routeName' => 'products.bulk-upload-page'])

    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Bulk Upload Products</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                Import hundreds of {{ $hubLabel ?? 'marketplace' }} items from a CSV file.
            </p>
        </div>
        <a href="{{ route('products.index', ['hub' => $hub ?? 'kidz_mart']) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-arrow-left mr-2"></i>Back to products
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-4xl">
        @if ($errors->any())
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('bulk_upload_errors') && count(session('bulk_upload_errors')) > 0)
            <div class="mb-4 bg-amber-50 border border-amber-300 text-amber-900 px-4 py-3 rounded">
                <p class="font-semibold mb-2">Upload notes</p>
                <ul class="list-disc list-inside text-sm max-h-48 overflow-y-auto">
                    @foreach (array_slice(session('bulk_upload_errors'), 0, 20) as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    @if (count(session('bulk_upload_errors')) > 20)
                        <li>... and {{ count(session('bulk_upload_errors')) - 20 }} more</li>
                    @endif
                </ul>
            </div>
        @endif

        <div class="space-y-8">
            <div class="border border-gray-200 rounded-lg p-5">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Step 1 — Download template</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Use these columns: <code class="text-xs bg-gray-100 px-1 py-0.5 rounded">item_name, grade, price, stock, image, delivery_days, quality, quantity</code>
                </p>
                <a href="{{ route('products.bulk-upload-template', ['hub' => $hub ?? 'kidz_mart']) }}"
                   class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg">
                    <i class="fas fa-download mr-2"></i>Download CSV template
                </a>
            </div>

            <div class="border border-gray-200 rounded-lg p-5">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Step 2 — Upload filled CSV</h2>
                <form action="{{ route('products.bulk-upload') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="hub" value="{{ $hub ?? 'kidz_mart' }}">

                    <div class="mb-4">
                        <label for="csv_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            CSV file <span class="text-red-500">*</span>
                        </label>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv,.txt" required
                               class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-xs text-gray-500">Max 10MB. UTF-8 CSV with a header row.</p>
                    </div>

                    <button type="submit"
                            class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg">
                        <i class="fas fa-upload mr-2"></i>Upload products
                    </button>
                </form>
            </div>

            <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Column guide</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-600">
                                <th class="py-2 pr-4">Column</th>
                                <th class="py-2 pr-4">Required</th>
                                <th class="py-2">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            <tr><td class="py-1 pr-4 font-mono">item_name</td><td>Yes</td><td>Product name</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">grade</td><td>No</td><td>e.g. P3, S4, PP1, All</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">price</td><td>Yes</td><td>Regular price in UGX</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">stock</td><td>Yes*</td><td>Inventory count (*or use quantity)</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">quantity</td><td>Yes*</td><td>Same as stock if stock is empty</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">image</td><td>No</td><td>Public image URL (downloaded automatically)</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">delivery_days</td><td>No</td><td>Defaults to 3 days</td></tr>
                            <tr><td class="py-1 pr-4 font-mono">quality</td><td>No</td><td>standard, premium, or economy</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
