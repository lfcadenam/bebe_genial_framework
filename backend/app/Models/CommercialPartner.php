<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommercialPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_name',
        'contact_email',
        'contact_phone',
        'is_agreement',
        'status',
        'allowed_courses',
        'allowed_months',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_agreement' => 'boolean',
            'allowed_courses' => 'array',
            'allowed_months' => 'integer',
        ];
    }

    /**
     * Alumnos / usuarios matriculados por este distribuidor o convenio
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'created_by');
    }
}
