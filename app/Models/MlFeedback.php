<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MlFeedback extends Model
{
    use HasFactory;

    protected $table = 'ml_feedback';

    protected $fillable = [
        'ml_prediction_id',
        'user_id',
        'feedback_type',
        'outcome',
        'notes',
    ];

    public function prediction(): BelongsTo
    {
        return $this->belongsTo(MlPrediction::class, 'ml_prediction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
