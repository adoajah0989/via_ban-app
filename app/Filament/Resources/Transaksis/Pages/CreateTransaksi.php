<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use App\Models\tb_toko;
use App\Services\TransaksiDetailService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateTransaksi extends CreateRecord
{
    protected static string $resource = TransaksiResource::class;
    protected array $limbahQuantities = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->limbahQuantities = (array) ($data['limbah_qty'] ?? []);

        $toko = null;
        $kodeWilayah = null;
        $idPusat = null;
        if (! empty($data['id_toko'])) {
            $toko = tb_toko::with('pusat')->find((int) $data['id_toko']);
            $kodeWilayah = $toko?->kode_wilayah;
            $idPusat = $toko?->id_pusat;
        }
        $summary = TransaksiDetailService::summarize($this->limbahQuantities, $kodeWilayah, $idPusat);

        $data['total_pickup'] = $summary['total_pickup'];
        $data['sales'] = $summary['total_sales'];
        $data['kode_wilayah'] = $kodeWilayah;
        return Arr::except($data, ['limbah_qty', 'details']);
    }

    protected function afterCreate(): void
    {
        // Simpan detail dari limbah_qty
        $this->syncDetailsFromQuantities($this->limbahQuantities);
        $this->recomputeTotals();
        $this->limbahQuantities = [];
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
