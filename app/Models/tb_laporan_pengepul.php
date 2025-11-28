<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_laporan_pengepul extends Model
{
    protected $table = 'tb_laporan_pengepul';

    protected $fillable = [
        'id_pengepul',
        'bulan',
        'format',
        'path',
        'grand_total',
    ];

    protected $casts = [
        'bulan' => 'date',
        'grand_total' => 'float',
    ];

    public function pengepul()
    {
        return $this->belongsTo(tb_pengepul::class, 'id_pengepul', 'id_pengepul');
    }
}

