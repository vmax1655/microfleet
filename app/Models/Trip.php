<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_number',
        'dispatch_id',
        'vehicle_id',
        'driver_id',
        'route_id',
        'departed_at',
        'arrived_at',
        'start_odometer_km',
        'end_odometer_km',
        'distance_km',
        'status',
        'return_notes',
    ];

    protected function casts(): array
    {
        return [
            'departed_at' => 'datetime',
            'arrived_at' => 'datetime',
            'start_odometer_km' => 'decimal:2',
            'end_odometer_km' => 'decimal:2',
            'distance_km' => 'decimal:2',
        ];
    }

    public function dispatch(): BelongsTo
    {
        return $this->belongsTo(Dispatch::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(TripLocation::class);
    }

    public function fuelTransactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(TripExpense::class);
    }

    public function transportCost(): HasOne
    {
        return $this->hasOne(TransportCost::class);
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(MlPrediction::class);
    }
}
