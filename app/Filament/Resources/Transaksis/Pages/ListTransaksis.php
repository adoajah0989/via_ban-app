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
                    $kodeWilayah = tb_toko::whereKey($data['id_toko'] ?? null)->value('kode_wilayah');
                    $summary = TransaksiDetailService::summarize((array) ($data['limbah_qty'] ?? []), $kodeWilayah);

                    $data['total_pickup'] = $summary['total_pickup'];
                    $data['sales'] = $summary['total_sales'];
                    return $data;
                })
                ->using(function (array $data) {
                    $kodeWilayah = tb_toko::whereKey($data['id_toko'] ?? null)->value('kode_wilayah');
                    $summary = TransaksiDetailService::summarize((array) ($data['limbah_qty'] ?? []), $kodeWilayah);

                    unset($data['details']);

                    $record = Transaksi::create(array_merge($data, [
                        'total_pickup' => $summary['total_pickup'],
                        'sales' => $summary['total_sales'],
                    ]));

                    if (! empty($summary['rows'])) {
                        $record->details()->createMany($summary['rows']);
                    }

                    return $record;
                }),
        ];
    }
}
