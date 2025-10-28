<?php

namespace App\Filament\Widgets;

use App\Models\detail_transaksi;
use App\Models\tb_limbah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LimbahTotals extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $limbahList = tb_limbah::query()
            ->orderBy('nama_limbah')
            ->get(['id_limbah', 'nama_limbah']);


        $totals = detail_transaksi::query()
            ->select('id_limbah', DB::raw('SUM(jumlah) as total'))
            ->groupBy('id_limbah')
            ->pluck('total', 'id_limbah');

        $limbahList = $limbahList->sortByDesc(function ($l) use ($totals) {
            return (int) ($totals[$l->id_limbah] ?? 0);
        });

        $stats = [];
        foreach ($limbahList as $l) {
            $value = (int) ($totals[$l->id_limbah] ?? 0);
            $stats[] = Stat::make($l->nama_limbah, number_format($value))
            ->color('success');
            
        }

        return $stats;
    }
}
