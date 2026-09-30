<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Family;
use App\Models\FillingSession;
use App\Models\ElectricityReading;
use App\Models\Settings;

class SessionController extends Controller
{
    /**
     * GET /api/sessions?familyId=...
     * Returns weekly sessions for a family.
     */
    public function index(Request $request)
    {
        $validated = $request->validate(['familyId' => 'required|integer']);
        $family = $this->findFamily($validated['familyId']);

        $now = now();
        $dayOfWeek = $now->dayOfWeek;
        $daysSinceSaturday = ($dayOfWeek + 1) % 7;
        $weekStart = $now->copy()->subDays($daysSinceSaturday)->startOfDay();

        $sessions = $family->sessions()
            ->where('start_time', '>=', $weekStart)
            ->orderBy('start_time', 'desc')
            ->get();

        $totalSeconds = $sessions->sum('duration');

        return response()->json([
            'sessions' => $sessions,
            'totalSeconds' => $totalSeconds,
            'weekStart' => $weekStart->toIso8601String(),
        ]);
    }

    /**
     * POST /api/sessions { familyId }
     * Starts a new filling session. Auto-links the latest unlinked BEFORE
     * electricity reading for this family to the new session.
     */
    public function start(Request $request)
    {
        $validated = $request->validate(['familyId' => 'required|integer']);
        $family = $this->findFamily($validated['familyId']);

        $active = $family->sessions()->whereNull('end_time')->first();
        if ($active) {
            return response()->json(['error' => 'يوجد جلسة نشطة بالفعل لهذه العائلة'], 400);
        }

        return DB::transaction(function () use ($family) {
            $session = FillingSession::create([
                'family_id' => $family->id,
                'start_time' => now(),
                'duration' => 0,
            ]);

            // Auto-link the latest unlinked BEFORE reading
            $before = ElectricityReading::where('family_id', $family->id)
                ->where('phase', 'BEFORE')
                ->whereNull('filling_session_id')
                ->orderBy('created_at', 'desc')
                ->first();
            if ($before) {
                $before->update(['filling_session_id' => $session->id]);
            }

            return response()->json($session->fresh(), 201);
        });
    }

    /**
     * PUT /api/sessions { sessionId, duration? }
     * Stops a session. Computes duration from start_time → now (always > 0).
     * Recomputes electricity cost if both BEFORE + AFTER readings exist.
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'sessionId' => 'required|integer',
            'duration' => 'nullable|integer|min:0',
        ]);

        $session = FillingSession::find($validated['sessionId']);
        if (!$session) {
            return response()->json(['error' => 'الجلسة غير موجودة'], 404);
        }

        $family = $this->findFamily($session->family_id);

        $endAt = now();
        $computedDuration = max(0, $endAt->getTimestamp() - $session->start_time->getTimestamp());
        $finalDuration = max($computedDuration, $validated['duration'] ?? 0);

        $session->update([
            'end_time' => $endAt,
            'duration' => $finalDuration,
        ]);

        // Recompute electricity cost if BEFORE + AFTER readings exist
        $before = ElectricityReading::where('filling_session_id', $session->id)
            ->where('phase', 'BEFORE')
            ->orderBy('created_at', 'desc')
            ->first();
        $after = ElectricityReading::where('filling_session_id', $session->id)
            ->where('phase', 'AFTER')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($before && $after) {
            $settings = Auth::user()->getSettings();
            $tariff = $settings->electricity_tariff;
            $kwh = max(0, round($after->reading - $before->reading, 2));
            $cost = $tariff !== null ? round($kwh * $tariff, 2) : null;
            $durationMin = $finalDuration / 60;
            $pricePerMin = ($cost !== null && $durationMin > 0)
                ? round($cost / $durationMin, 2)
                : null;

            $session->update([
                'kwh_consumed' => $kwh,
                'electricity_cost' => $cost,
                'price_per_minute' => $pricePerMin,
            ]);
        }

        return response()->json($session->fresh());
    }

    /**
     * PATCH /api/sessions/reset?familyId=...
     * Deletes all sessions for a family (weekly reset).
     */
    public function reset(Request $request)
    {
        $validated = $request->validate(['familyId' => 'required|integer']);
        $family = $this->findFamily($validated['familyId']);

        $family->sessions()->delete();

        return response()->json(['success' => true]);
    }

    private function findFamily(int $familyId): Family
    {
        $family = Family::where('id', $familyId)
            ->where('user_id', Auth::id())
            ->first();
        if (!$family) {
            // SUPER_ADMIN can access any family
            if (Auth::user()->isSuperAdmin()) {
                $family = Family::find($familyId);
            }
            abort_if(!$family, 404, 'العائلة غير موجودة');
        }
        return $family;
    }
}
