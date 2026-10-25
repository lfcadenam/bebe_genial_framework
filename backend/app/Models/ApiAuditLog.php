<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'partner_id',
        'campaign_code',
        'endpoint',
        'method',
        'http_status',
        'ip_address',
        'requested_email',
        'payload',
        'response',
        'execution_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'execution_time_ms' => 'integer',
            'payload' => 'array',
            'response' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ApiPartner::class, 'partner_id');
    }
}
