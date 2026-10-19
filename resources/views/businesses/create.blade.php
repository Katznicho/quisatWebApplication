<x-app-layout title="Create Business - GreensWell">
    <x-dashboard-layout header="Create Business">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <form action="{{ route('businesses.store') }}" method="POST" enctype="multipart/form-data" class="px-4 py-5 sm:p-6">
                    @csrf

                    <!-- Name -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Business Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name') }}"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="Enter business name"
                        >
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Logo Upload -->
                    <div class="mb-6">
                        <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">
                            Business Logo
                        </label>
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <input 
                                    type="file" 
                                    name="logo" 
                                    id="logo" 
                                    accept="image/*"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-accent-50 file:text-primary-700 hover:file:bg-accent-100"
                                    onchange="previewImage(this)"
                                >
                                @error('logo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-2 text-xs text-gray-500">Max size: 2MB. Formats: JPEG, PNG, JPG, GIF, SVG</p>
                            </div>
                            <div id="preview" class="hidden">
                                <img id="preview-img" src="" alt="Logo preview" class="h-20 w-20 rounded-full object-cover border-2 border-gray-300">
                            </div>
                        </div>
                    </div>

                    <!-- Account Balance -->
                    <div class="mb-6">
                        <label for="account_balance" class="block text-sm font-medium text-gray-700 mb-2">
                            Initial Account Balance (UGX)
                        </label>
                        <input 
                            type="number" 
                            name="account_balance" 
                            id="account_balance" 
                            value="{{ old('account_balance', 0) }}"
                            min="0"
                            step="0.01"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="0.00"
                        >
                        @error('account_balance')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Cost Per SMS -->
                    <div class="mb-6">
                        <label for="cost_per_sms" class="block text-sm font-medium text-gray-700 mb-2">
                            Cost Per SMS (UGX) <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="cost_per_sms" 
                            id="cost_per_sms" 
                            value="{{ old('cost_per_sms', 30.00) }}"
                            min="0"
                            step="0.01"
                            required
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="30.00"
                        >
                        @error('cost_per_sms')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">The cost per SMS for this business.</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <a href="{{ route('businesses.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            Create Business
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
        <script>
            function previewImage(input) {
                const preview = document.getElementById('preview');
                const previewImg = document.getElementById('preview-img');
                
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.classList.remove('hidden');
                    }
                    
                    reader.readAsDataURL(input.files[0]);
                } else {
                    preview.classList.add('hidden');
                }
            }
        </script>
        @endpush
    </x-dashboard-layout>
</x-app-layout>
