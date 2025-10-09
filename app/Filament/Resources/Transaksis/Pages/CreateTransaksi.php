<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\tb_limbah;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $details = $data['details'] ?? [];
        $totalPickup = 0;
        $totalSales = 0;

        if (! empty($details)) {
            $ids = array_column($details, 'id_limbah');
            $prices = tb_limbah::whereIn('id_limbah', $ids)->pluck('harga', 'id_limbah');

            foreach ($details as $row) {
                $qty = (int) ($row['jumlah'] ?? 0);
                $totalPickup += $qty;
                $harga = (int) ($prices[$row['id_limbah']] ?? 0);
                $totalSales += ($qty * $harga);
            }
        }

        $data['total_pickup'] = $totalPickup;
        $data['sales'] = $totalSales;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->recomputeTotals();
    }

    private function recomputeTotals(): void
    {
        $record = $this->record->load('details.limbah');
        $totalPickup = (int) ($record->details->sum('jumlah'));
        $totalSales = (int) ($record->details->sum(function ($d) {
            return (int) ($d->jumlah ?? 0) * (int) ($d->limbah->harga ?? 0);
        }));

        $record->update([
            'total_pickup' => $totalPickup,
            'sales' => $totalSales,
        ]);
    }
}
