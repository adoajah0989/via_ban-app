<?php

namespace App\Filament\Resources\Limbahs\Pages;

use App\Filament\Resources\Limbahs\LimbahResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\ViewAction;

class EditLimbah extends EditRecord
{
    protected static string $resource = LimbahResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Modifikasi data sebelum disimpan jika diperlukan
        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Hapus'),
        ];
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

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Data limbah berhasil diperbarui';
    }
}
