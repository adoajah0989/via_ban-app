<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\MockObject\NoMoreReturnValuesConfiguredException;

class tb_pengepul extends Model
{
    protected $table = 'tb_pengepul';
    protected $primaryKey = 'id_pengepul';

    protected $fillable = [
        'nama',
        'nomor_telepon',
        'nomor_kendaraan'
    ];

    public function telegramAccount()
    {
        return $this->hasOne(tb_telegram_user::class, 'id_pengepul', 'id_pengepul')
            ->where('role', 'pengepul');
    }
}
