<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportCost extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'fuel_cost',
        'expense_cost',
        'maintenance_allocation',
        'total_cost',
        'cost_per_km',
        'center_code',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'fuel_cost' => 'decimal:2',
            'expense_cost' => 'decimal:2',
            'maintenance_allocation' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'cost_per_km' => 'decimal:2',
        ];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}
