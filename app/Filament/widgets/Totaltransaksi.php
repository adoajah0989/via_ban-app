<?php

namespace App\Filament\Widgets;

use App\Models\tb_transaksi;
use Carbon\Carbon;
use Filament\Tables\Concerns\HasFilters;
use Filament\Schemas\Schema;
use Filament\Forms\Components\DatePicker;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Facades\DB;



class Totaltransaksi extends ChartWidget
{
    use HasFiltersSchema;
    protected bool $isCollapsible = true;
    protected ?string $heading = 'Total transaksi';
    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('startDate')
                ->default(now()->subMonths(6)),
            DatePicker::make('endDate')
                ->default(now()->endOfMonth()),
        ]);
    }
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
            // ->where('status', 'selesai') // ✅ hanya hitung transaksi selesai
            ->select(DB::raw("DATE_FORMAT(tanggal, '%Y-%m') as ym"))
            ->selectRaw('COUNT(*) as total') // ✅ perbaiki COUNT syntax
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $data = $months->map(fn($m) => (int) ($raw[$m['ym']] ?? 0))->all();
        $labels = $months->map(fn($m) => $m['label'])->all();

        return [
            'datasets' => [
                [
                    'label' => ' Transaksi',
                    'data' => $data,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.3,
                    'fill' => true,

                ],
            ],
            'labels' => $labels,

        ];
    }
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'ticks' => [
                        // Menampilkan hanya bilangan bulat
                        'stepSize' => 1,
                        'beginAtZero' => true,
                    ],
                ],
            ],
        ];
    }
    protected function getType(): string
    {
        return 'line';
    }
}
