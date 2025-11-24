<?php

namespace App\Services;

use App\Models\tb_telegram_user;

class TelegramUserResolver
{
    /**
     * Temukan akun telegram berdasarkan telegram_user_id.
     *
     * @return array{role:string|null,pengepul_id:int|null,admin_id:int|null}
     */
    public static function resolveByTelegramId(int $telegramUserId): array
    {
        $record = tb_telegram_user::query()
            ->where('telegram_user_id', $telegramUserId)
            ->where('is_active', true)
            ->first();

        if (! $record) {
            return [
                'role' => null,
                'pengepul_id' => null,
                'admin_id' => null,
            ];
        }

        return [
            'role' => $record->role,
            'pengepul_id' => $record->role === 'pengepul' ? $record->id_pengepul : null,
            'admin_id' => $record->role === 'admin' ? $record->id_user : null,
        ];
    }
}

