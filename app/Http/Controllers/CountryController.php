<?php

namespace App\Http\Controllers;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CountryController extends Controller
{
    public function index()
    {
        $this->authorizeSuperAdmin();

        $countries = Country::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('countries.index', compact('countries'));
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:countries,name',
            'currency_code' => 'required|string|max:10',
            'currency_name' => 'nullable|string|max:100',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'is_default' => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Country::query()->update(['is_default' => false]);
        }

        Country::create([
            'name' => $validated['name'],
            'currency_code' => strtoupper($validated['currency_code']),
            'currency_name' => $validated['currency_name'] ?? null,
            'exchange_rate' => $validated['exchange_rate'],
            'is_default' => !empty($validated['is_default']),
        ]);

        return redirect()->route('countries.index')->with('success', 'Country created successfully.');
    }

    public function update(Request $request, Country $country)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:countries,name,' . $country->id,
            'currency_code' => 'required|string|max:10',
            'currency_name' => 'nullable|string|max:100',
            'exchange_rate' => 'required|numeric|min:0.000001',
            'is_default' => 'nullable|boolean',
        ]);

        if (!empty($validated['is_default'])) {
            Country::query()->where('id', '!=', $country->id)->update(['is_default' => false]);
        }

        $country->update([
            'name' => $validated['name'],
            'currency_code' => strtoupper($validated['currency_code']),
            'currency_name' => $validated['currency_name'] ?? null,
            'exchange_rate' => $validated['exchange_rate'],
            'is_default' => !empty($validated['is_default']),
        ]);

        return redirect()->route('countries.index')->with('success', 'Country updated successfully.');
    }

    public function destroy(Country $country)
    {
        $this->authorizeSuperAdmin();

        if ($country->is_default) {
            return redirect()->route('countries.index')->with('error', 'Default country cannot be deleted.');
        }

        if ($country->businesses()->exists()) {
            return redirect()->route('countries.index')->with('error', 'Country is in use by one or more businesses.');
        }

        $country->delete();

        return redirect()->route('countries.index')->with('success', 'Country deleted successfully.');
    }

    protected function authorizeSuperAdmin(): void
    {
        if (!Auth::check() || (int) Auth::user()->business_id !== 1) {
            abort(403, 'Only superadmin can manage countries.');
        }
    }
}
