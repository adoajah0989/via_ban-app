<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use App\Models\tb_toko;
use App\Services\TransaksiDetailService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditTransaksi extends EditRecord
{
    protected static string $resource = TransaksiResource::class;
    protected array $limbahQuantities = [];

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Lihat'),
            DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->limbahQuantities = (array) ($data['limbah_qty'] ?? []);

        $tokoId = (int) ($data['id_toko'] ?? $this->record->id_toko);
        $toko = tb_toko::with('pusat')->find($tokoId);
        $kodeWilayah = $toko?->kode_wilayah;
        $idPusat = $toko?->id_pusat;
        $summary = TransaksiDetailService::summarize($this->limbahQuantities, $kodeWilayah, $idPusat);

        $data['total_pickup'] = $summary['total_pickup'];
        $data['sales'] = $summary['total_sales'];
        $data['kode_wilayah']  = $kodeWilayah;
        return Arr::except($data, ['limbah_qty', 'details']);
    }

    protected function afterSave(): void
    {
        // Simpan detail dari limbah_qty
        $this->syncDetailsFromQuantities($this->limbahQuantities);
        $this->recomputeTotals();
        $this->limbahQuantities = [];
    }

    private function recomputeTotals(): void
    {
        $record = $this->record->load('details');
        $totalPickup = (int) ($record->details->sum('jumlah'));
        $totalSales = (int) ($record->details->sum(function ($d) {
            return (int) ($d->jumlah ?? 0) * (int) ($d->harga_saat_transaksi ?? 0);
        }));

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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data transaksi berhasil diperbarui';
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()
            ->label('Simpan Perubahan');
    }
    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
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
