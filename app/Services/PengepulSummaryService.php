<?php

namespace App\Services;

use App\Models\tb_transaksi;
use Carbon\Carbon;

class PengepulSummaryService
{
    /**
     * Ringkasan singkat untuk pengepul: berapa toko yang sudah diangkut
     * dan statistik transaksi terkait.
     */
    public static function buildSummaryForPengepul(int $pengepulId): array
    {
        $query = tb_transaksi::query()
            ->where('id_pengepul', $pengepulId);

        $totalToko = (clone $query)
            ->distinct('id_toko')
            ->count('id_toko');

        $pending = (clone $query)
            ->where('status', 'pending')
            ->count();

        $selesai = (clone $query)
            ->where('status', 'selesai')
            ->count();

        $totalTagihan = (clone $query)
            ->where('status', 'selesai')
            ->sum('sales');

        return [
            'pending' => $pending,
            'selesai' => $selesai,
            'toko_total' => $totalToko,
            'total_tagihan' => $totalTagihan,
        ];
    }

    /**
     * Ringkasan untuk pengepul dalam rentang tanggal tertentu.
     */
    public static function buildSummaryForPengepulInRange(int $pengepulId, Carbon $start, Carbon $end): array
    {
        $query = tb_transaksi::query()
            ->where('id_pengepul', $pengepulId)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()]);

        $pendingCount = (clone $query)
            ->where('status', 'pending')
            ->count();

        $pendingTotal = (clone $query)
            ->where('status', 'pending')
            ->sum('sales');

        $selesaiCount = (clone $query)
            ->where('status', 'selesai')
            ->count();

        $selesaiTotal = (clone $query)
            ->where('status', 'selesai')
            ->sum('sales');

        $totalToko = (clone $query)
            ->distinct('id_toko')
            ->count('id_toko');

        return [
            'pending_count' => $pendingCount,
            'pending_total' => $pendingTotal,
            'selesai_count' => $selesaiCount,
            'selesai_total' => $selesaiTotal,
            'toko_total' => $totalToko,
        ];
    }
}
