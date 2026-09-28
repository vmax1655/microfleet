<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_number',
        'requester_user_id',
        'route_id',
        'vehicle_type_id',
        'preferred_driver_id',
        'purpose',
        'passenger_count',
        'load_level',
        'scheduled_start_at',
        'scheduled_end_at',
        'predicted_fuel_liters',
        'predicted_cost',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'predicted_fuel_liters' => 'decimal:2',
            'predicted_cost' => 'decimal:2',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(TransportRoute::class, 'route_id');
    }

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function preferredDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'preferred_driver_id');
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    public function dispatch(): HasOne
    {
        return $this->hasOne(Dispatch::class)->latestOfMany();
    }

    public function activeDispatch(): HasOne
    {
        return $this->hasOne(Dispatch::class)->latestOfMany();
    }

    public function predictions(): HasMany
    {
        return $this->hasMany(MlPrediction::class);
    }
}
