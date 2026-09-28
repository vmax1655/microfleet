<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_number',
        'license_number',
        'license_restrictions',
        'license_expires_at',
        'medical_clearance_expires_at',
        'phone',
        'availability_status',
        'safety_score',
    ];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'medical_clearance_expires_at' => 'date',
            'safety_score' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(Dispatch::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function fuelTransactions(): HasMany
    {
        return $this->hasMany(FuelTransaction::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class);
    }

    public function transportCosts(): HasManyThrough
    {
        return $this->hasManyThrough(TransportCost::class, Trip::class);
    }

    public function licenseCategoryLabel(): string
    {
        $code = strtoupper((string) $this->license_restrictions);
        if (str_contains($code, 'B2') || str_contains($code, 'N03')) {
            return 'Code B2/C · Commercial & Heavy (Pickup, Van, Multi-cab, Motorcycle)';
        }
        if (str_contains($code, 'B1') || str_contains($code, 'CODE B,') || str_contains($code, 'N02')) {
            return 'Code B/B1 · Passenger Van & Multi-cab (Van, Multi-cab, Motorcycle)';
        }
        if (str_contains($code, 'A1')) {
            return 'Code A1 · Tricycle & Light Multi-cab';
        }
        return 'Code A · Motorcycle Only';
    }

    public function authorizedVehicleTypes(): array
    {
        $code = strtoupper((string) $this->license_restrictions);
        if ($code === '' || ! str_contains($code, 'CODE')) {
            return ['Motorcycle', 'Multi-cab', 'Passenger Van', 'Utility Pickup'];
        }
        if (str_contains($code, 'B2') || str_contains($code, 'N03')) {
            return ['Motorcycle', 'Multi-cab', 'Passenger Van', 'Utility Pickup'];
        }
        if (str_contains($code, 'B1') || str_contains($code, 'N02')) {
            return ['Motorcycle', 'Multi-cab', 'Passenger Van'];
        }
        if (str_contains($code, 'A1')) {
            return ['Motorcycle', 'Multi-cab'];
        }
        return ['Motorcycle'];
    }

    public function isQualifiedForVehicle(string $vehicleTypeName): bool
    {
        return in_array($vehicleTypeName, $this->authorizedVehicleTypes(), true);
    }
}
