<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareAccount extends Model
{
    protected $fillable = ['name', 'email', 'api_key', 'api_token'];

    public function managedDomains()
    {
        return $this->hasMany(ManagedDomain::class);
    }

    public function domains()
    {
        return $this->hasMany(CloudflareDomain::class);
    }
}
