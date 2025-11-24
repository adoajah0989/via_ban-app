<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tb_pusat_toko extends Model
{
    protected $table = 'tb_pusat_toko';
    protected $primaryKey = 'id_pusat';

    protected $fillable = [
        'nama_pusat',
        'kode_pusat',
        'kontak',
    ];

    public function tokos()
    {
        return $this->hasMany(tb_toko::class, 'id_pusat', 'id_pusat');
    }

    public function limbah()
    {
        return $this->hasMany(tb_limbah::class, 'id_pusat', 'id_pusat');
    }
}

