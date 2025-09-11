<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_wilayah extends Model
{
    protected $table = 'tb_wilayah';
    protected $primaryKey = 'id_wilayah';

    protected $fillable = [
        'nama_wilayah',
        'kode_wilayah',
    ];
}
