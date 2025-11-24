<?php

namespace App\Filament\Resources\Pengepuls\Pages;

use App\Filament\Resources\Pengepuls\PengepulResource;
use App\Models\tb_telegram_user;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPengepul extends EditRecord
{
    protected static string $resource = PengepulResource::class;

    protected ?int $telegramUserId = null;
    protected ?string $telegramUsername = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Lihat'),
            DeleteAction::make()->label('Hapus'),
            ForceDeleteAction::make()->label('Hapus Permanen'),
            RestoreAction::make()->label('Pulihkan'),
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
        return 'Data pengepul berhasil diperbarui';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $account = $this->record->telegramAccount;
        if ($account) {
            $data['telegram_user_id'] = $account->telegram_user_id;
            $data['telegram_username'] = $account->username;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->telegramUserId = isset($data['telegram_user_id']) && $data['telegram_user_id'] !== ''
            ? (int) $data['telegram_user_id']
            : null;
        $this->telegramUsername = $data['telegram_username'] ?? null;

        unset($data['telegram_user_id'], $data['telegram_username']);

        return $data;
    }

    protected function afterSave(): void
    {
        $existing = $this->record->telegramAccount;

        if ($this->telegramUserId) {
            tb_telegram_user::updateOrCreate(
                ['telegram_user_id' => $this->telegramUserId],
                [
                    'role' => 'pengepul',
                    'id_pengepul' => $this->record->id_pengepul,
                    'username' => $this->telegramUsername,
                    'is_active' => true,
                ]
            );
        } elseif ($existing) {
            $existing->delete();
        }
    }
}
