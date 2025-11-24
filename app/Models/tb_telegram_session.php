<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_telegram_session extends Model
{
    protected $table = 'tb_telegram_session';

    protected $fillable = [
        'telegram_user_id',
        'state',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}

