<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Models\tb_limbah;
use App\Models\tb_transaksi as Transaksi;

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
                    $qtys = (array) ($data['limbah_qty'] ?? []);
                    $positive = array_filter($qtys, fn ($q) => (int) $q > 0);
                    $ids = array_map('intval', array_keys($positive));
                    $totalPickup = 0;
                    $totalSales = 0;
                    if (! empty($ids)) {
                        $prices = tb_limbah::whereIn('id_limbah', $ids)->pluck('harga', 'id_limbah');
                        foreach ($positive as $id => $qty) {
                            $q = (int) $qty;
                            $totalPickup += $q;
                            $harga = (int) ($prices[(int) $id] ?? 0);
                            $totalSales += ($q * $harga);
                        }
                    }
                    $data['total_pickup'] = (int) $totalPickup;
                    $data['sales'] = (int) $totalSales;
                    return $data;
                })
                ->using(function (array $data) {
                    $record = Transaksi::create($data);
                    $qtys = (array) ($data['limbah_qty'] ?? []);
                    $positive = array_filter($qtys, fn ($q) => (int) $q > 0);
                    $ids = array_map('intval', array_keys($positive));
                    $prices = [];
                    if (! empty($ids)) {
                        $prices = tb_limbah::whereIn('id_limbah', $ids)->pluck('harga', 'id_limbah')->toArray();
                    }
                    $rows = [];
                    foreach ($positive as $id => $qty) {
                        $rows[] = [
                            'id_limbah' => (int) $id,
                            'jumlah' => (int) $qty,
                            'harga_saat_transaksi' => (int) ($prices[(int) $id] ?? 0),
                        ];
                    }
                    if (! empty($rows)) {
                        $record->details()->createMany($rows);
                    }
                    return $record;
                }),
        ];
    }
}
