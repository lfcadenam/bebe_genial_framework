<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WompiPlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'price',
        'currency',
        'duration_months',
        'trial_days',
        'is_active',
        'benefits',
        'related_courses',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_months' => 'integer',
            'trial_days' => 'integer',
            'is_active' => 'boolean',
            'related_courses' => 'array',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(WompiSubscription::class, 'plan_id');
    }
}
