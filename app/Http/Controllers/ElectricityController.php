<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Family;
use App\Models\FillingSession;
use App\Models\ElectricityReading;
use App\Models\Settings;

class ElectricityController extends Controller
{
    /**
     * GET /api/electricity?familyId=...
     * Returns last 50 readings for a family.
     */
    public function index(Request $request)
    {
        $validated = $request->validate(['familyId' => 'required|integer']);
        $family = $this->findFamily($validated['familyId']);

        $readings = $family->electricityReadings()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($readings);
    }

    /**
     * POST /api/electricity
     * Saves a meter reading. Triggers cost computation when AFTER + sessionId + BEFORE exist.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'familyId' => 'required|integer',
            'reading' => 'required|numeric',
            'phase' => 'nullable|string|in:BEFORE,AFTER,STANDALONE',
            'source' => 'nullable|string|in:OCR,MANUAL',
            'sessionId' => 'nullable|integer',
            'confidence' => 'nullable|numeric',
            'engine' => 'nullable|string',
            'photoThumb' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $family = $this->findFamily($validated['familyId']);

        // Previous reading = latest reading for this family
        $previous = ElectricityReading::where('family_id', $family->id)
            ->orderBy('created_at', 'desc')
            ->first();
        $prevVal = $previous?->reading;
        $consumption = $prevVal !== null
            ? round($validated['reading'] - $prevVal, 2)
            : null;

        $phase = $validated['phase'] ?? 'STANDALONE';
        $sessionId = $validated['sessionId'] ?? null;

        // If no explicit sessionId but phase=BEFORE, leave unlinked
        // (POST /api/sessions will link it on session start).
        $reading = ElectricityReading::create([
            'family_id' => $family->id,
            'filling_session_id' => $sessionId,
            'reading' => $validated['reading'],
            'previous_reading' => $prevVal,
            'consumption' => $consumption,
            'phase' => $phase,
            'source' => $validated['source'] ?? 'MANUAL',
            'confidence' => $validated['confidence'] ?? null,
            'engine' => $validated['engine'] ?? null,
            'photo_thumb' => $validated['photoThumb'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // If AFTER + sessionId + BEFORE exist → compute cost on session
        if ($phase === 'AFTER' && $sessionId) {
            $this->recomputeSession($sessionId, Auth::user()->getSettings());
        }

        return response()->json(['reading' => $reading->fresh()], 201);
    }

    /**
     * GET /api/electricity/estimate-price?familyId=...
     * Returns the estimated price/min (engine × tariff ÷ 60) and last actual reading.
     */
    public function estimatePrice(Request $request)
    {
        $validated = $request->validate(['familyId' => 'required|integer']);
        $family = $this->findFamily($validated['familyId']);

        $settings = Auth::user()->getSettings();
        $tariff = $settings->electricity_tariff;
        $enginePowerKw = $settings->engine_power_kw;
        $estimatedPerMin = ($tariff !== null && $enginePowerKw !== null)
            ? round(($enginePowerKw * $tariff) / 60, 4)
            : null;

        // Last session with cost data for this family
        $lastSession = $family->sessions()
            ->whereNotNull('price_per_minute')
            ->orderBy('created_at', 'desc')
            ->first();
        $lastActual = null;
        if ($lastSession) {
            $lastActual = [
                'pricePerMinute' => $lastSession->price_per_minute,
                'electricityCost' => $lastSession->electricity_cost,
                'kwhConsumed' => $lastSession->kwh_consumed,
                'durationSec' => $lastSession->duration,
            ];
        }

        return response()->json([
            'tariff' => $tariff,
            'enginePowerKw' => $enginePowerKw,
            'estimatedPricePerMin' => $estimatedPerMin,
            'lastActual' => $lastActual,
        ]);
    }

    /**
     * DELETE /api/electricity/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $reading = ElectricityReading::where('id', $id)->first();
        if (!$reading) {
            return response()->json(['error' => 'غير موجود'], 404);
        }

        $family = Family::where('id', $reading->family_id)
            ->where('user_id', Auth::id())
            ->first();
        if (!$family && !Auth::user()->isSuperAdmin()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        $reading->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Internal: recompute kwh/cost/pricePerMin on a session.
     */
    private function recomputeSession(int $sessionId, Settings $settings): void
    {
        $session = FillingSession::find($sessionId);
        if (!$session) return;

        $before = ElectricityReading::where('filling_session_id', $sessionId)
            ->where('phase', 'BEFORE')
            ->orderBy('created_at', 'desc')
            ->first();
        $after = ElectricityReading::where('filling_session_id', $sessionId)
            ->where('phase', 'AFTER')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$before || !$after) return;

        $tariff = $settings->electricity_tariff;
        $kwh = max(0, round($after->reading - $before->reading, 2));
        $cost = $tariff !== null ? round($kwh * $tariff, 2) : null;

        // duration might be 0 here (AFTER captured before stop) → recompute on stop
        $durationMin = $session->duration / 60;
        $pricePerMin = ($cost !== null && $durationMin > 0)
            ? round($cost / $durationMin, 2)
            : null;

        $session->update([
            'kwh_consumed' => $kwh,
            'electricity_cost' => $cost,
            'price_per_minute' => $pricePerMin,
        ]);
    }

    private function findFamily(int $familyId): Family
    {
        $family = Family::where('id', $familyId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$family && Auth::user()->isSuperAdmin()) {
            $family = Family::find($familyId);
        }
        abort_if(!$family, 404, 'العائلة غير موجودة');
        return $family;
    }
}
