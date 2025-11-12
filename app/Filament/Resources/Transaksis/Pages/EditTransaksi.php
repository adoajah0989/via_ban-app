<?php

namespace App\Filament\Resources\Transaksis\Pages;

use App\Filament\Resources\Transaksis\TransaksiResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use App\Models\tb_limbah;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditTransaksi extends EditRecord
{
    protected static string $resource = TransaksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Lihat'),
            DeleteAction::make()->label('Hapus'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hitung total berdasarkan input non-repeater: limbah_qty[id_limbah] = jumlah
        $qtys = (array) ($data['limbah_qty'] ?? []);
        $positive = array_filter($qtys, fn($q) => (int) $q > 0);
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
        unset($data['details']); // tidak lagi memakai repeater

        return $data;
    }

    protected function afterSave(): void
    {
        // Simpan detail dari limbah_qty
        $this->syncDetailsFromQuantities((array) ($this->data['limbah_qty'] ?? []));
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

    private function syncDetailsFromQuantities(array $qtys): void
    {
        $positive = array_filter($qtys, fn($q) => (int) $q > 0);
        $ids = array_map('intval', array_keys($positive));
        $prices = [];
        if (! empty($ids)) {
            $prices = tb_limbah::whereIn('id_limbah', $ids)->pluck('harga', 'id_limbah')->toArray();
        }

        // Reset dan simpan ulang detail
        $this->record->details()->delete();
        $rows = [];
        foreach ($positive as $id => $qty) {
            $rows[] = [
                'id_limbah' => (int) $id,
                'jumlah' => (int) $qty,
                'harga_saat_transaksi' => (int) ($prices[(int) $id] ?? 0),
            ];
        }
        if (! empty($rows)) {
            $this->record->details()->createMany($rows);
        }
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data transaksi berhasil diperbarui';
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Simpan Perubahan');
    }
    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()
            ->label('Batal');
    }
}
