<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'subject',
        'body_html',
        'available_variables',
    ];

    protected function casts(): array
    {
        return [
            'available_variables' => 'array',
        ];
    }
}
