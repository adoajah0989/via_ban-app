<?php

namespace App\Models;



use Illuminate\Database\Eloquent\Model;
use App\Models\detail_transaksi;
use Carbon\Carbon;
use App\Services\TransaksiNotificationService;

class tb_transaksi extends Model
{
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'id_transaksi';
    public $timestamps = true; // Set to true if your table has created_at and updated_at columns

    protected $fillable = [
        'tanggal',
        'id_toko',
        'kode_transaksi',
        'sales',
        'total_pickup',
        'status',
        'id_pengepul',
        'kode_wilayah',
    ];
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = 'pending';
            }

            if (empty($model->kode_transaksi)) {
                $date = $model->tanggal
                    ? Carbon::parse($model->tanggal)
                    : Carbon::now();

                $prefix = $date->format('ymd'); // YYMMDD

                // Hitung urutan transaksi pada tanggal tersebut (semua toko),
                // supaya kode_transaksi tetap unik per hari.
                $sequence = (int) self::query()
                    ->whereDate('tanggal', $date->toDateString())
                    ->count() + 1;

                // Batasi 2 digit (01-99) untuk menjaga panjang 8 karakter.
                if ($sequence > 99) {
                    $sequence = 99;
                }

                $model->kode_transaksi = $prefix . str_pad((string) $sequence, 2, '0', STR_PAD_LEFT);
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
     public function pengepul()
    {
        return $this->belongsTo(tb_pengepul::class, 'id_pengepul', 'id_pengepul');
    }
}
