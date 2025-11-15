<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use App\Models\tb_toko;
use App\Services\TransaksiDetailService;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $kodeWilayah = $this->resolveKodeWilayah((int) ($data['id_toko'] ?? 0));
        $summary = TransaksiDetailService::summarize((array) ($data['limbah_qty'] ?? []), $kodeWilayah);

        $data['total_pickup'] = $summary['total_pickup'];
        $data['sales'] = $summary['total_sales'];
        unset($data['details']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Simpan detail dari limbah_qty
        $this->syncDetailsFromQuantities((array) ($this->data['limbah_qty'] ?? []));
        $this->recomputeTotals();
    }

    private function recomputeTotals(): void
    {
        $record = $this->record->load('details');
        $totalPickup = (int) $record->details->sum('jumlah');
        $totalSales = (int) $record->details->sum(function ($detail) {
            return (int) ($detail->jumlah ?? 0) * (int) ($detail->harga_saat_transaksi ?? 0);
        });

        $record->update([
            'total_pickup' => $totalPickup,
            'sales' => $totalSales,
        ]);
    }

    private function syncDetailsFromQuantities(array $qtys): void
    {
        $kodeWilayah = $this->recordKodeWilayah();
        $summary = TransaksiDetailService::summarize($qtys, $kodeWilayah);

        $this->record->details()->delete();
        if (! empty($summary['rows'])) {
            $this->record->details()->createMany($summary['rows']);
        }
    }

    private function resolveKodeWilayah(?int $tokoId): ?string
    {
        if (! $tokoId) {
            return null;
        }

        return tb_toko::whereKey($tokoId)->value('kode_wilayah');
    }

    private function recordKodeWilayah(): ?string
    {
        $this->record->loadMissing('toko');

        return $this->record->toko->kode_wilayah ?? null;
    }
}
