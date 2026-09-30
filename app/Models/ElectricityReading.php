<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ElectricityReading extends Model
{
    use HasFactory;

    protected $table = 'electricity_readings';

    protected $fillable = [
        'family_id',
        'filling_session_id',
        'reading',
        'previous_reading',
        'consumption',
        'phase',
        'source',
        'confidence',
        'engine',
        'photo_thumb',
        'notes',
    ];

    protected $casts = [
        'reading' => 'float',
        'previous_reading' => 'float',
        'consumption' => 'float',
        'confidence' => 'float',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(FillingSession::class, 'filling_session_id');
    }
}
