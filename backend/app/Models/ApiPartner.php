<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiPartner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'origin_code',
        'name',
        'partner_code',
        'api_key',
        'ip_whitelist',
        'contact_name',
        'contact_email',
        'ghl_access_token',
        'etiqueta',
        'status',
    ];

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'partner_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ApiAuditLog::class, 'partner_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
