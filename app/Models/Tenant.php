<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    protected $fillable = [
        'remote_id',
        'name',
        'domain',
        'server_id',
        'managed_domain_id',
        'status',
        'data',
        'nameservers',
        'cloudflare_status',
    ];

    protected $casts = [
        'data' => 'array',
        'nameservers' => 'array',
    ];

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function domains()
    {
        return $this->hasMany(TenantDomain::class);
    }

    public function managedDomain()
    {
        return $this->belongsTo(ManagedDomain::class);
    }
}
