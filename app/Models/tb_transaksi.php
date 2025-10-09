<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;
use App\Models\detail_transaksi;

class tb_transaksi extends Model
{
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'id_transaksi';
    public $timestamps = true; // Set to true if your table has created_at and updated_at columns

    protected $fillable = [
        'tanggal',
        'id_toko',
        'total_pickup',
        'sales',
        'status',
        'id_pengepul',

    ];
    protected static function booted()
{
    static::creating(function ($model) {
        if (empty($model->status)) {
            $model->status = 'pending';
        }
    });
}

     public function details()
    {
        return $this->hasMany(detail_transaksi::class, 'id_transaksi', 'id_transaksi');
    }
     public function toko()
    {
        return $this->belongsTo(tb_toko::class, 'id_toko', 'id_toko');
    }
}
