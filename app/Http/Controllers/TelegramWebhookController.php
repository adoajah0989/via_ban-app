<?php

namespace App\Http\Controllers;

use App\Models\tb_telegram_user;
use App\Services\TelegramUserResolver;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $update = $request->all();
        Log::info('telegram-webhook', $update);

        // Handle callback query (inline keyboard)
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query'];
            $from = $callback['from'] ?? [];
            $telegramUserId = (int) ($from['id'] ?? 0);
            $chatId = (int) ($callback['message']['chat']['id'] ?? 0);
            $data = (string) ($callback['data'] ?? '');

            $identity = TelegramUserResolver::resolveByTelegramId($telegramUserId);

            if ($data === 'pengepul_menu_summary') {
                TelegramBotService::sendMessage($chatId, "Ringkasan data pengepul akan dikirim dari sistem (fitur dalam pengembangan).");
            } elseif ($data === 'pengepul_menu_add') {
                TelegramBotService::sendMessage($chatId, "Fitur tambah data dari bot sedang disiapkan.\nUntuk sementara, silakan input transaksi dari panel web.");
            } elseif ($data === 'admin_menu_validate') {
                TelegramBotService::sendMessage($chatId, "Fitur validasi transaksi via bot sedang disiapkan.");
            }

            return response()->noContent();
        }

        // Handle text message
        $message = $update['message'] ?? $update['edited_message'] ?? null;
        if (! $message) {
            return response()->noContent();
        }

        $from = $message['from'] ?? [];
        $telegramUserId = (int) ($from['id'] ?? 0);
        if (! $telegramUserId) {
            return response()->noContent();
        }

        $chatId = (int) ($message['chat']['id'] ?? 0);
        $text = trim((string) ($message['text'] ?? ''));

        $identity = TelegramUserResolver::resolveByTelegramId($telegramUserId);

        if ($text === '/start') {
            if ($identity['role'] === 'pengepul') {
                TelegramBotService::sendPengepulMainMenu($chatId);
            } elseif ($identity['role'] === 'admin') {
                TelegramBotService::sendAdminMainMenu($chatId);
            } else {
                // Auto-daftarkan akun Telegram sebagai pending supaya admin bisa menghubungkan
                tb_telegram_user::updateOrCreate(
                    ['telegram_user_id' => $telegramUserId],
                    [
                        'role' => 'pengepul', // default, bisa diubah admin
                        'username' => $from['username'] ?? null,
                        'is_active' => false,
                    ]
                );

                TelegramBotService::sendMessage(
                    $chatId,
                    "Akun Telegram ini belum terdaftar di sistem.\n"
                    . "Silakan hubungi admin dan berikan ID berikut:\n\n"
                    . "`{$telegramUserId}`"
                );
            }

            return response()->noContent();
        }

        // Untuk sementara, balas default berdasarkan role.
        if ($identity['role'] === 'pengepul') {
            TelegramBotService::sendMessage($chatId, "Perintah belum dikenali.\nGunakan /start untuk membuka menu pengepul.");
        } elseif ($identity['role'] === 'admin') {
            TelegramBotService::sendMessage($chatId, "Perintah belum dikenali.\nGunakan /start untuk membuka menu admin.");
        } else {
            TelegramBotService::sendMessage($chatId, "Akun belum terdaftar. Gunakan /start lalu hubungi admin.");
        }

        return response()->noContent();
    }
}
