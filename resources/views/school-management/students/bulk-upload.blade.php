<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                            {{ __('Bulk Upload Students') }}
                        </h2>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Upload multiple students at once using a CSV file
                        </p>
                    </div>
                    <a href="{{ route('school-management.students') }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Students
                    </a>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <div class="whitespace-pre-line">{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('bulk_upload_errors') && count(session('bulk_upload_errors')) > 0)
                    <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                        <strong class="font-bold">Bulk Upload Errors:</strong>
                        <ul class="list-disc list-inside mt-2">
                            @foreach (array_slice(session('bulk_upload_errors'), 0, 10) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @if (count(session('bulk_upload_errors')) > 10)
                                <li>... and {{ count(session('bulk_upload_errors')) - 10 }} more errors</li>
                            @endif
                        </ul>
                    </div>
                @endif

                <!-- Step 1: Download Template -->
                <div class="mb-8 p-6 bg-blue-50 dark:bg-gray-700 rounded-lg border border-blue-200 dark:border-gray-600">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-blue-600 text-white font-bold text-lg">
                                1
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Download CSV Template
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Download the template file to see the required format and column structure. The template includes example rows to guide you.
                            </p>
                            <a href="{{ route('school-management.students.download-template') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Download Template
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Upload CSV -->
                <div class="mb-8 p-6 bg-green-50 dark:bg-gray-700 rounded-lg border border-green-200 dark:border-gray-600">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <div class="flex items-center justify-center h-10 w-10 rounded-full bg-green-600 text-white font-bold text-lg">
                                2
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Upload Filled CSV File
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                Fill in the template with your student data and upload it here. Make sure the parent/guardian email exists in the system.
                            </p>
                            
                            <form action="{{ route('school-management.students.bulk-upload') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="mb-4">
                                    <label for="csv_file" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Select CSV File <span class="text-red-500">*</span>
                                    </label>
                                    <input type="file" 
                                           name="csv_file" 
                                           id="csv_file" 
                                           accept=".csv,.txt"
                                           required
                                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                        Maximum file size: 10MB. Allowed formats: CSV, TXT
                                    </p>
                                </div>
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                    </svg>
                                    Upload CSV File
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Instructions -->
                <div class="p-6 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        CSV Format Instructions
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Required Columns:</h4>
                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li><strong>first_name</strong> - Student's first name</li>
                                <li><strong>last_name</strong> - Student's last name</li>
                                <li><strong>email</strong> - Valid email address (must be unique)</li>
                                <li><strong>date_of_birth</strong> - Date of birth (format: YYYY-MM-DD)</li>
                                <li><strong>gender</strong> - Must be one of: male, female, other</li>
                                <li><strong>student_id</strong> - Unique student ID</li>
                                <li><strong>admission_date</strong> - Admission date (format: YYYY-MM-DD)</li>
                                <li><strong>parent_email</strong> - Email of parent/guardian (must exist in system)</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Optional Columns:</h4>
                            <ul class="list-disc list-inside text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <li><strong>phone</strong> - Phone number</li>
                                <li><strong>address</strong> - Street address</li>
                                <li><strong>city</strong> - City name</li>
                                <li><strong>country</strong> - Country name</li>
                                <li><strong>status</strong> - Must be: active, inactive, graduated, or transferred (defaults to active if not provided)</li>
                            </ul>
                        </div>
                        <div class="mt-4 p-3 bg-yellow-50 dark:bg-gray-600 rounded border border-yellow-200 dark:border-gray-500">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <strong>Important:</strong> The CSV file must have a header row with column names. Each row after the header represents one student. Empty rows will be skipped. The parent/guardian must be created in the system before uploading students.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
