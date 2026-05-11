<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudflareDomain extends Model
{
    protected $fillable = [
        'zone_id',
        'cloudflare_account_id',
        'name',
        'status',
        'name_servers',
        'paused',
        'subdomains',
        'last_synced_at',
    ];

    protected $casts = [
        'name_servers' => 'array',
        'subdomains' => 'array',
        'paused' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function cloudflareAccount(): BelongsTo
    {
        return $this->belongsTo(CloudflareAccount::class);
    }
}
