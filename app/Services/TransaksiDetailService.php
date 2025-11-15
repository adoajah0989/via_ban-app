<?php

namespace App\Services;

use App\Services\HargaWilayahResolver;

class TransaksiDetailService
{
    /**
     * @param  array<int|string, mixed>  $rawQuantities
     * @return array{total_pickup:int,total_sales:int,rows:array<int,array{id_limbah:int,jumlah:int,harga_saat_transaksi:int}>}
     */
    public static function summarize(array $rawQuantities, ?string $kodeWilayah): array
    {
        $quantities = array_filter($rawQuantities, fn ($q) => (int) $q > 0);
        $ids = array_map('intval', array_keys($quantities));
        $prices = HargaWilayahResolver::getFor($ids, $kodeWilayah);

        $totalPickup = 0;
        $totalSales = 0;
        $rows = [];

        foreach ($quantities as $id => $qty) {
            $id = (int) $id;
            $jumlah = (int) $qty;
            $harga = (int) ($prices[$id] ?? 0);

            $totalPickup += $jumlah;
            $totalSales += $jumlah * $harga;

            $rows[] = [
                'id_limbah' => $id,
                'jumlah' => $jumlah,
                'harga_saat_transaksi' => $harga,
            ];
        }

        return [
            'total_pickup' => $totalPickup,
            'total_sales' => $totalSales,
            'rows' => $rows,
        ];
    }
}
