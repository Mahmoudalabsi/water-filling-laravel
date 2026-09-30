<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Settings;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $settings = Auth::user()->getSettings();
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'freeMinutesPerWeek' => 'nullable|integer|min:0|max:10000',
            'pricePerMinute' => 'nullable|numeric|min:0',
            'autoResetWeekly' => 'nullable|boolean',
            'resetDay' => 'nullable|integer|min:0|max:6',
            'electricityTariff' => 'nullable|numeric|min:0',
            'enginePowerKw' => 'nullable|numeric|min:0',
        ]);

        $settings = Auth::user()->getSettings();
        $settings->update([
            'free_minutes_per_week' => $validated['freeMinutesPerWeek'] ?? $settings->free_minutes_per_week,
            'price_per_minute' => $validated['pricePerMinute'] ?? $settings->price_per_minute,
            'auto_reset_weekly' => $validated['autoResetWeekly'] ?? $settings->auto_reset_weekly,
            'reset_day' => $validated['resetDay'] ?? $settings->reset_day,
            'electricity_tariff' => $validated['electricityTariff'] ?? $settings->electricity_tariff,
            'engine_power_kw' => $validated['enginePowerKw'] ?? $settings->engine_power_kw,
        ]);

        return response()->json($settings->fresh());
    }
}
