<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_wilayah extends Model
{
    protected $table = 'tb_wilayah';
    protected $primaryKey = 'kode_wilayah';

    // Karena bukan auto-increment
    public $incrementing = false;
    public $timestamps = true;
    // Karena tipe-nya string (VARCHAR)
    protected $keyType = 'string';
    protected $fillable = [
        'nama_wilayah',
        'kode_wilayah',
    ];
}
