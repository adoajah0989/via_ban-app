<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_toko extends Model
{
    protected $table = 'tb_toko';
    protected $primaryKey = 'id_toko';
    
    public function setKodeTokoAttribute($value)
    {
        $this->attributes['kode_toko'] = $value;
        $this->attributes['kode_wilayah'] = substr((string) $value, 0, 3);
    }
    protected $fillable = [
        'id_pusat',
        'nama_toko',
        'kode_toko',
        'kode_wilayah',
        'alamat',
        'nomor_telepon',
    ];
    

    public function getNamaTokoAttribute($value)
    {
        if ($value) {
            return ucwords(strtoupper($value));
        }
    }

    public function pusat()
    {
        return $this->belongsTo(tb_pusat_toko::class, 'id_pusat', 'id_pusat');
    }
}
