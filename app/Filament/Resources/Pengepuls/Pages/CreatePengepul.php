<?php

namespace App\Filament\Resources\Pengepuls\Pages;

use App\Filament\Resources\Pengepuls\PengepulResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\tb_telegram_user;

class CreatePengepul extends CreateRecord
{
    protected static string $resource = PengepulResource::class;

    protected ?int $telegramUserId = null;
    protected ?string $telegramUsername = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->telegramUserId = isset($data['telegram_user_id']) && $data['telegram_user_id'] !== ''
            ? (int) $data['telegram_user_id']
            : null;
        $this->telegramUsername = $data['telegram_username'] ?? null;

        unset($data['telegram_user_id'], $data['telegram_username']);

        return $data;
    }

    protected function afterCreate(): void
    {
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
        }
    }
}
