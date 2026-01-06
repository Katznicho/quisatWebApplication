@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Kids Mart Products</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Manage your products and inventory</p>
    </div>

    @livewire('list-products')
</div>
@endsection

