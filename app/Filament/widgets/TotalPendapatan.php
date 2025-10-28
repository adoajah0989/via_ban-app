<?php

namespace App\Filament\Widgets;

use App\Models\tb_transaksi;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TotalPendapatan extends ChartWidget
{
    protected ?string $heading = 'Total Pendapatan';

    protected function getData(): array
    {
        $end = Carbon::now()->endOfMonth();
        $start = Carbon::now()->subMonths(6)->startOfMonth();

        $months = collect(range(0, 6))->map(function ($i) {
            $date = Carbon::now()->subMonths(6 - $i);
            return [
                'ym' => $date->format('Y-m'),
                'label' => $date->format('M y'),
            ];
        });

        $raw = tb_transaksi::query()
            ->whereBetween('tanggal', [$start, $end])
            ->select(DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as ym"))
            ->selectRaw('SUM(sales) as total')
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $data = $months->map(fn($m) => (float) ($raw[$m['ym']] ?? 0))->all();
        $labels = $months->map(fn($m) => $m['label'])->all();

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan',
                    'data' => $data,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }
        protected bool $isCollapsible = true;

    protected function getType(): string
    {
        return 'line';
    }
}
