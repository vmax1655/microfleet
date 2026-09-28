<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class TransportRoute extends Model
{
    use HasFactory;

    protected $table = 'routes';

    protected $fillable = [
        'origin_depot_id',
        'route_code',
        'name',
        'center_code',
        'destination_name',
        'destination_latitude',
        'destination_longitude',
        'planned_distance_km',
        'estimated_duration_minutes',
        'road_profile',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'destination_latitude' => 'decimal:7',
            'destination_longitude' => 'decimal:7',
            'planned_distance_km' => 'decimal:2',
        ];
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'origin_depot_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'route_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'route_id');
    }

    public function transportCosts(): HasManyThrough
    {
        return $this->hasManyThrough(TransportCost::class, Trip::class, 'route_id', 'trip_id');
    }
}
