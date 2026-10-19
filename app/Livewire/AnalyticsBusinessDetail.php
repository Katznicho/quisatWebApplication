<?php

namespace App\Livewire;

use App\Models\Business;
use Livewire\Component;

class AnalyticsBusinessDetail extends Component
{
    public Business $business;
    public $activeTab = 'users';

    public function mount(Business $business)
    {
        $this->business = $business->load(['package', 'users']);
    }

    public function render()
    {
        return view('livewire.analytics-business-detail');
    }
}

