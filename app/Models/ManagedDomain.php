<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagedDomain extends Model
{
    protected $fillable = [
        'domain_name',
        'subdomain',
        'cloudflare_account_id',
        'server_id',
        'cloudflare_zone_id',
        'status',
        'last_error'
    ];

    public function cloudflareAccount()
    {
        return $this->belongsTo(CloudflareAccount::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }
}
