<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PadresContenido extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'padres_contenidos';

    protected $fillable = [
        'section',
        'category',
        'title',
        'resource_type',
        'url_video',
        'file_path',
        'thumbnail',
        'description',
        'order_index',
        'allow_download',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'order_index' => 'integer',
            'allow_download' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
