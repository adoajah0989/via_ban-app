<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_limbah extends Model
{
    protected $table = 'tb_limbah';
    protected $primaryKey = 'id_limbah';
    public $timestamps = false;
    protected $fillable = [
        'nama_limbah',
        'harga',
        'kode_limbah'
    ];
}
