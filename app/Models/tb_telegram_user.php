<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_telegram_user extends Model
{
    protected $table = 'tb_telegram_user';

    protected $fillable = [
        'telegram_user_id',
        'username',
        'phone',
        'role',
        'id_pengepul',
        'id_user',
        'is_active',
    ];

    public function pengepul()
    {
        return $this->belongsTo(tb_pengepul::class, 'id_pengepul', 'id_pengepul');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}

