<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FillingSession extends Model
{
    use HasFactory;

    protected $table = 'filling_sessions';

    protected $fillable = [
        'family_id',
        'start_time',
        'end_time',
        'duration',
        'kwh_consumed',
        'electricity_cost',
        'price_per_minute',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'kwh_consumed' => 'float',
        'electricity_cost' => 'float',
        'price_per_minute' => 'float',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function electricityReadings(): HasMany
    {
        return $this->hasMany(ElectricityReading::class, 'filling_session_id');
    }

    public function isActive(): bool
    {
        return $this->end_time === null;
    }
}
