<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Settings extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'free_minutes_per_week',
        'price_per_minute',
        'auto_reset_weekly',
        'reset_day',
        'last_auto_reset',
        'electricity_tariff',
        'engine_power_kw',
    ];

    protected $casts = [
        'auto_reset_weekly' => 'boolean',
        'last_auto_reset' => 'datetime',
        'price_per_minute' => 'float',
        'electricity_tariff' => 'float',
        'engine_power_kw' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
