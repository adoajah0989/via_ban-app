<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class TelegramBotService
{
    /**
     * Kirim pesan ke Telegram.
     *
     * Jika paket Telegram Bot SDK for PHP (irazasyed/telegram-bot-sdk)
     * sudah terpasang, service ini otomatis akan memakainya.
     * Jika tidak, fallback ke HTTP API biasa.
     */
    public static function sendMessage(int $chatId, string $text, bool $useMarkdown = true): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            return;
        }

        $params = [
            'chat_id' => $chatId,
            'text' => $text,
        ];

        if ($useMarkdown) {
            $params['parse_mode'] = 'Markdown';
        }

        // Jika SDK tersedia, gunakan SDK agar lebih ringkas.
        if (class_exists(\Telegram\Bot\Api::class)) {
            $telegram = new \Telegram\Bot\Api($token);
            $telegram->sendMessage($params);

            return;
        }

        // Fallback: panggil HTTP API langsung.
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        Http::asForm()->post($url, $params)->throw();
    }

    /**
     * Kirim file PDF nota transaksi ke chat tertentu.
     */
    public static function sendDocumentFromPath(int $chatId, string $path, string $caption = ''): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token || ! is_file($path)) {
            return;
        }

        $filename = basename($path);

        try {
            if (class_exists(\Telegram\Bot\Api::class)) {
                $telegram = new \Telegram\Bot\Api($token);
                $telegram->sendDocument([
                    'chat_id' => $chatId,
                    'document' => fopen($path, 'rb'),
                    'caption' => $caption,
                ]);

                return;
            }

            $url = "https://api.telegram.org/bot{$token}/sendDocument";

            Http::attach('document', fopen($path, 'rb'), $filename)
                ->asMultipart()
                ->post($url, [
                    'chat_id' => $chatId,
                    'caption' => $caption,
                ])
                ->throw();
        } catch (\Throwable $e) {
            Log::error('telegram:sendDocument-failed', [
                'chat_id' => $chatId,
                'path' => $path,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function sendPengepulMainMenu(int $chatId): void
    {
        $text = "Selamat datang di bot pengepul.\nPilih salah satu menu berikut:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Ringkasan', 'callback_data' => 'pengepul_menu_summary'],
                ],
                [
                    ['text' => '🧾 Menu Transaksi', 'callback_data' => 'pengepul_menu_transaksi'],
                ],
            ],
        ];

        self::sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    public static function sendAdminMainMenu(int $chatId): void
    {
        $text = "Selamat datang di bot admin.\nPilih salah satu menu berikut:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Validasi Transaksi', 'callback_data' => 'admin_menu_validate'],
                ],
            ],
        ];

        self::sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    public static function sendPengepulTransaksiMenu(int $chatId): void
    {
        $text = "Menu Transaksi:\nSilakan pilih tindakan:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '➕ Input Data', 'callback_data' => 'pengepul_trx_add'],
                ],
                [
                    ['text' => '✏️ Edit Data (pending)', 'callback_data' => 'pengepul_trx_edit'],
                ],
                [
                    ['text' => '🗑 Hapus Data (pending)', 'callback_data' => 'pengepul_trx_delete'],
                ],
            ],
        ];

        self::sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    public static function sendPengepulInputModeMenu(int $chatId): void
    {
        $text = "Input data transaksi baru.\nPilih jenis input:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Real-time (hari ini)', 'callback_data' => 'pengepul_trx_mode_realtime'],
                ],
                [
                    ['text' => 'Pilih tanggal', 'callback_data' => 'pengepul_trx_mode_manual'],
                ],
            ],
        ];

        self::sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    public static function sendMessageWithKeyboard(int $chatId, string $text, array $keyboard): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboard),
        ];

        if (class_exists(\Telegram\Bot\Api::class)) {
            $telegram = new \Telegram\Bot\Api($token);
            $telegram->sendMessage($payload);

            return;
        }

        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        Http::asForm()->post($url, $payload)->throw();
    }
}
