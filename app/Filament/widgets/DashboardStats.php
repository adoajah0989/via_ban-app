<?php

namespace App\Filament\Widgets;

use App\Models\tb_toko;
use App\Models\tb_transaksi;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalNominal = (float) (tb_transaksi::query()->sum('sales'));
        $jumlahToko = (int) (tb_toko::query()->count());
        $jumlahTransaksi = (int) (tb_transaksi::query()
        ->where('status', 'selesai')
        ->count()
    );

        return [
            Stat::make('Total Nominal', 'Rp ' . number_format($totalNominal, 0, ',', '.')),
            Stat::make('Jumlah Toko', number_format($jumlahToko)),
            Stat::make('Jumlah Transaksi', number_format($jumlahTransaksi)),
        ];
    }
}
