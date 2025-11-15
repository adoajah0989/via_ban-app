<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_harga_wilayah extends Model
{
    protected $table = 'tb_harga_wilayah';

    protected $fillable = [
        'id_limbah',
        'kode_wilayah',
        'harga',
    ];

    public function wilayah()
    {
        return $this->belongsTo(tb_wilayah::class, 'kode_wilayah', 'kode_wilayah');
    }

    public function limbah()
    {
        return $this->belongsTo(tb_limbah::class, 'id_limbah', 'id_limbah');
    }
}
