<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainInfo extends Model
{
    protected $guarded = [];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];
}
