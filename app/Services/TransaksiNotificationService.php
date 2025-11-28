<?php

namespace App\Services;

use App\Models\tb_transaksi as Transaksi;
use Illuminate\Support\Facades\Log;

class TransaksiNotificationService
{
    /**
     * Kirim nota transaksi ke pengepul terkait via Telegram, jika akun Telegram tersedia.
     */
    public static function sendNotaToPengepul(Transaksi $transaksi): void
    {
        $transaksi->loadMissing('pengepul.telegramAccount');
        $pengepul = $transaksi->pengepul;
        $account = $pengepul?->telegramAccount;

        if (! $account || ! $account->telegram_user_id) {
             Log::info('transaksi-nota:skip-no-telegram', [
                 'transaksi_id' => $transaksi->id_transaksi,
                 'pengepul_id' => $pengepul->id_pengepul ?? null,
             ]);
            return;
        }

        $path = TransaksiNotaService::generatePdf($transaksi);
        if (! $path) {
            Log::error('transaksi-nota:failed-generate-pdf', [
                'transaksi_id' => $transaksi->id_transaksi,
            ]);
            return;
        }

        $caption = 'Nota transaksi ' . ($transaksi->kode_transaksi ?? $transaksi->id_transaksi);
        $chatId = (int) $account->telegram_user_id;

        Log::info('transaksi-nota:send', [
            'transaksi_id' => $transaksi->id_transaksi,
            'chat_id' => $chatId,
            'path' => $path,
        ]);

        TelegramBotService::sendDocumentFromPath(
            $chatId,
            $path,
            $caption
        );
    }
}
