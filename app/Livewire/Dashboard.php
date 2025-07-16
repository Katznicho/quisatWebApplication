<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $business;
    public $balance;
    public $lastUpdate;

    public function mount()
    {
        $user = Auth::user();

        // Load the business relationship
        $this->business = $user->business;

        // dd($this->business->name);

        $this->balance = $user->balance;
        $this->lastUpdate = now()->format('H:i:s');
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
