<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryRead extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
        'read_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'story_id' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
