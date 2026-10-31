<?php

namespace App\Http\Controllers;

use App\Models\Canteen;
use App\Models\CanteenSale;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;

class PocketPayController extends Controller
{
    public function canteens()
    {
        PocketPayAccess::authorize();

        return view('pocket-pay.canteens', $this->nav());
    }

    public function showCanteen(Canteen $canteen)
    {
        PocketPayAccess::authorize();
        TenantScope::authorizeModel($canteen);

        $today = $canteen->sales()->where('status', CanteenSale::STATUS_COMPLETED)->whereDate('created_at', today());

        return view('pocket-pay.canteen', $this->nav() + [
            'canteen' => $canteen->load('business:id,name'),
            'salesToday' => (float) (clone $today)->sum('amount'),
            'salesCountToday' => (clone $today)->count(),
            'pendingTopups' => $canteen->topups()->whereNull('credited_at')->whereIn('status', ['pending', 'processing'])->sum('amount'),
        ]);
    }

    public function cards()
    {
        PocketPayAccess::authorize();

        return view('pocket-pay.cards', $this->nav());
    }

    public function reversals()
    {
        PocketPayAccess::authorize();

        return view('pocket-pay.reversals', $this->nav());
    }

    protected function nav(): array
    {
        return [
            'pendingReversals' => TenantScope::apply(CanteenSale::query())
                ->where('reversal_status', CanteenSale::REVERSAL_PENDING)
                ->count(),
        ];
    }
}
