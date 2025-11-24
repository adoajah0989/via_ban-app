<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_limbah extends Model
{
    protected $table = 'tb_limbah';
    protected $primaryKey = 'id_limbah';
    public $timestamps = false;
    protected $fillable = [
        'id_pusat',
        'nama_limbah',
        'harga',
        'kode_limbah'
    ];

    public function pusat()
    {
        return $this->belongsTo(tb_pusat_toko::class, 'id_pusat', 'id_pusat');
    }
}
