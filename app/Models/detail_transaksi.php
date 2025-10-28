<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class detail_transaksi extends Model
{
    protected $table = 'detail_transaksi';
    protected $primaryKey = 'id_detail';
    public $timestamps = false;
    protected $fillable = ['id_transaksi', 'id_limbah', 'jumlah', 'harga_saat_transaksi'];

    public function transaksi()
    {
        return $this->belongsTo(tb_transaksi::class, 'id_transaksi', 'id_transaksi');
    }
    
public function limbah()
    {
        return $this->belongsTo(tb_limbah::class, 'id_limbah', 'id_limbah');
    }
}
