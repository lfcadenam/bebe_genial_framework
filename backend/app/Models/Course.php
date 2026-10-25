<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'cover_image',
        'background_image',
        'hours',
        'price',
        'is_visible',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'is_active' => 'boolean',
            'hours' => 'integer',
            'price' => 'decimal:2',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class, 'course_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'course_id');
    }
}
