<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Family;
use App\Models\FillingSession;
use App\Models\Settings;
use App\Models\ElectricityReading;

class HomeController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = Auth::user();

        // Get start of current week (Saturday midnight)
        $now = now();
        $dayOfWeek = $now->dayOfWeek; // 0=Sun, 6=Sat
        $daysSinceSaturday = ($dayOfWeek + 1) % 7;
        $weekStart = $now->copy()->subDays($daysSinceSaturday)->startOfDay();

        $families = $user->families()->with(['sessions' => function ($q) use ($weekStart) {
            $q->where('start_time', '>=', $weekStart)->orderBy('start_time', 'desc');
        }])->get();

        $totalSecondsThisWeek = 0;
        foreach ($families as $fam) {
            foreach ($fam->sessions as $s) {
                $totalSecondsThisWeek += $s->duration;
            }
        }

        $settings = $user->getSettings();
        $freeMinutes = $settings->free_minutes_per_week;
        $usedMinutes = floor($totalSecondsThisWeek / 60);
        $remainingMinutes = max(0, $freeMinutes - $usedMinutes);
        $freeSeconds = $freeMinutes * 60;
        $usedPct = $freeSeconds > 0 ? min(100, ($totalSecondsThisWeek / $freeSeconds) * 100) : 0;

        // Prepare JSON payload for the Alpine.js dashboard (moved here to keep Blade simple)
        $initialData = [
            'families' => $families->map(function ($f) {
                return [
                    'id' => $f->id,
                    'name' => $f->name,
                    'activeSession' => $f->sessions->first(function ($s) {
                        return $s->end_time === null;
                    }),
                    'lastSession' => $f->sessions->filter(function ($s) {
                        return $s->end_time !== null && $s->price_per_minute !== null;
                    })->first(),
                ];
            })->values(),
            'settings' => $settings,
        ];

        return view('dashboard', [
            'user' => $user,
            'families' => $families,
            'settings' => $settings,
            'initialData' => $initialData,
            'totalSeconds' => $totalSecondsThisWeek,
            'usedMinutes' => $usedMinutes,
            'remainingMinutes' => $remainingMinutes,
            'usedPct' => round($usedPct, 1),
            'weekStart' => $weekStart,
        ]);
    }
}
