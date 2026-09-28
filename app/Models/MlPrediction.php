<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlPrediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'trip_id',
        'model_name',
        'model_version',
        'predicted_fuel_liters',
        'predicted_cost',
        'actual_fuel_liters',
        'actual_cost',
        'variance_percent',
        'feature_payload',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'predicted_fuel_liters' => 'decimal:2',
            'predicted_cost' => 'decimal:2',
            'actual_fuel_liters' => 'decimal:2',
            'actual_cost' => 'decimal:2',
            'variance_percent' => 'decimal:2',
            'feature_payload' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
