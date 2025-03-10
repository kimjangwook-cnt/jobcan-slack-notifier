<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsSslInfo extends BaseModel
{

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'valid_to' => 'datetime:Y-m-d',
    ];
}
