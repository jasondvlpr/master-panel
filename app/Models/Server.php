<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    protected $fillable = ['name', 'ip', 'api_endpoint', 'api_key'];

    public function managedDomains()
    {
        return $this->hasMany(ManagedDomain::class);
    }
}
