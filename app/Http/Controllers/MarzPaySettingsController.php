<?php

namespace App\Http\Controllers;

use App\Models\MarzPaySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarzPaySettingsController extends Controller
{
    public function edit()
    {
        $this->authorizeSuperAdmin();

        $settings = MarzPaySetting::current();

        return view('marzpay.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'mobile_money_charge_type' => 'required|in:fixed,percent',
            'mobile_money_charge_value' => 'required|numeric|min:0',
            'card_charge_type' => 'required|in:fixed,percent',
            'card_charge_value' => 'required|numeric|min:0',
        ]);

        if ($validated['mobile_money_charge_type'] === 'percent' && $validated['mobile_money_charge_value'] > 100) {
            return back()->withInput()->withErrors([
                'mobile_money_charge_value' => 'Mobile money percentage charge cannot exceed 100%.',
            ]);
        }

        if ($validated['card_charge_type'] === 'percent' && $validated['card_charge_value'] > 100) {
            return back()->withInput()->withErrors([
                'card_charge_value' => 'Card percentage charge cannot exceed 100%.',
            ]);
        }

        MarzPaySetting::current()->update($validated);

        return redirect()
            ->route('marzpay.settings.edit')
            ->with('success', 'MarzPay settings updated successfully.');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (! Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only super administrators can manage MarzPay settings.');
        }
    }
}
