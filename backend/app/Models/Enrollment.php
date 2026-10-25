<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'campaign_id',
        'enrolled_at',
        'expires_at',
        'access_type',
        'book_limit',
        'origin_channel',
    ];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'date:Y-m-d',
            'expires_at' => 'date:Y-m-d',
            'book_limit' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /**
     * Determina si la matrícula está vigente a la fecha actual
     */
    public function isValid(): bool
    {
        return $this->expires_at >= now()->toDateString();
    }

    public function isFull(): bool
    {
        return $this->access_type === 'FULL';
    }

    public function isFreemium(): bool
    {
        return $this->access_type === 'FREEMIUM';
    }
}
