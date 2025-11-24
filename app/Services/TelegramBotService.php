<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramBotService
{
    /**
     * Kirim pesan ke Telegram.
     *
     * Jika paket Telegram Bot SDK for PHP (irazasyed/telegram-bot-sdk)
     * sudah terpasang, service ini otomatis akan memakainya.
     * Jika tidak, fallback ke HTTP API biasa.
     */
    public static function sendMessage(int $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            return;
        }

        // Jika SDK tersedia, gunakan SDK agar lebih ringkas.
        if (class_exists(\Telegram\Bot\Api::class)) {
            $telegram = new \Telegram\Bot\Api($token);
            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'Markdown',
            ]);

            return;
        }

        // Fallback: panggil HTTP API langsung.
        $url = "https://api.telegram.org/bot{$token}/sendMessage";

        Http::asForm()->post($url, [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ])->throw();
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
                    ['text' => '➕ Tambah Data', 'callback_data' => 'pengepul_menu_add'],
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

    protected static function sendMessageWithKeyboard(int $chatId, string $text, array $keyboard): void
    {
        $token = config('services.telegram.bot_token');
        if (! $token) {
            return;
        }

        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
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
