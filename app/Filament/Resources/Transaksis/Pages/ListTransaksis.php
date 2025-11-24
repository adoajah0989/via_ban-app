<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use App\Models\tb_toko;
use App\Models\tb_transaksi as Transaksi;
use App\Services\TransaksiDetailService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTransaksis extends ListRecords
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Transaksi Baru')
                ->modalHeading('Transaksi Baru')
                ->modalWidth('7xl')
                ->mutateFormDataUsing(function (array $data): array {
                    $toko = null;
                    $kodeWilayah = null;
                    $idPusat = null;
                    if (! empty($data['id_toko'])) {
                        $toko = tb_toko::with('pusat')->find((int) $data['id_toko']);
                        $kodeWilayah = $toko?->kode_wilayah;
                        $idPusat = $toko?->id_pusat;
                    }
                    $summary = TransaksiDetailService::summarize((array) ($data['limbah_qty'] ?? []), $kodeWilayah, $idPusat);

                    $data['total_pickup'] = $summary['total_pickup'];
                    $data['sales'] = $summary['total_sales'];
                    $data['kode_wilayah'] = $kodeWilayah;
                    return $data;
                })
                ->using(function (array $data) {
                    $toko = null;
                    $kodeWilayah = $data['kode_wilayah'] ?? null;
                    $idPusat = null;
                    if (! empty($data['id_toko'])) {
                        $toko = tb_toko::with('pusat')->find((int) $data['id_toko']);
                        $kodeWilayah = $kodeWilayah ?? $toko?->kode_wilayah;
                        $idPusat = $toko?->id_pusat;
                    }
                    $summary = TransaksiDetailService::summarize((array) ($data['limbah_qty'] ?? []), $kodeWilayah, $idPusat);

                    foreach (['details', 'limbah_qty'] as $helperField) {
                        unset($data[$helperField]);
                    }

                    $record = Transaksi::create(array_merge($data, [
                        'total_pickup' => $summary['total_pickup'],
                        'sales' => $summary['total_sales'],
                        'kode_wilayah' => $kodeWilayah,
                    ]));

                    if (! empty($summary['rows'])) {
                        $record->details()->createMany($summary['rows']);
                    }

                    return $record;
                }),
        ];
    }
}
