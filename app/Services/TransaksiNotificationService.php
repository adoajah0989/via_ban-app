<?php

namespace App\Services;

use App\Models\tb_transaksi as Transaksi;
use App\Models\tb_telegram_user;
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

    /**
     * Kirim notifikasi ke admin via Telegram saat ada transaksi pending baru atau diedit.
     * Notifikasi berisi link langsung ke halaman validasi transaksi.
     * 
     * @param Transaksi $transaksi
     * @param bool $isEdited True jika transaksi diedit, false jika transaksi baru
     */
    public static function notifyAdminNewTransaction(Transaksi $transaksi, bool $isEdited = false): void
    {
        $transaksi->loadMissing('toko', 'pengepul');

        // Ambil semua admin aktif yang punya akun Telegram
        $adminAccounts = tb_telegram_user::where('role', 'admin')
            ->where('is_active', true)
            ->whereNotNull('telegram_user_id')
            ->get();

        if ($adminAccounts->isEmpty()) {
            Log::info('transaksi-admin-notify:skip-no-admin-telegram', [
                'transaksi_id' => $transaksi->id_transaksi,
            ]);
            return;
        }

        $kode = $transaksi->kode_transaksi ?? $transaksi->id_transaksi;
        $toko = $transaksi->toko->nama_toko ?? '-';
        $pengepul = $transaksi->pengepul->nama ?? '-';
        $tanggal = \Carbon\Carbon::parse($transaksi->tanggal)->format('d M Y');
        $totalFormatted = number_format($transaksi->sales, 0, ',', '.');

        // Build validation link - menggunakan ngrok URL atau APP_URL dari config
        $baseUrl = config('app.url');
        $validationUrl = rtrim($baseUrl, '/') . '/admin/transaksis';

        $lines = [];
        
        if ($isEdited) {
            $lines[] = "✏️ *Data Telah Diedit - Perlu Validasi Ulang*";
            $lines[] = "";
            $lines[] = "Pengepul telah mengedit data transaksi.";
        } else {
            $lines[] = "🔔 *Transaksi Baru - Perlu Validasi*";
        }
        
        $lines[] = "";
        $lines[] = "Kode: `{$kode}`";
        $lines[] = "Toko: {$toko}";
        $lines[] = "Pengepul: {$pengepul}";
        $lines[] = "Tanggal: {$tanggal}";
        $lines[] = "Total: Rp {$totalFormatted}";
        $lines[] = "Status: ⏳ *Pending*";
        $lines[] = "";
        $lines[] = "👉 [Klik untuk validasi transaksi]({$validationUrl})";
        $lines[] = "";
        $lines[] = "_Silakan buka link di atas untuk melihat detail dan validasi transaksi._";

        $message = implode("\n", $lines);

        foreach ($adminAccounts as $admin) {
            $chatId = (int) $admin->telegram_user_id;

            Log::info('transaksi-admin-notify:send', [
                'transaksi_id' => $transaksi->id_transaksi,
                'admin_chat_id' => $chatId,
                'is_edited' => $isEdited,
            ]);

            TelegramBotService::sendMessage($chatId, $message);
        }
    }

    /**
     * Kirim notifikasi ke pengepul saat transaksi diverifikasi (status -> selesai).
     */
    public static function notifyPengepulTransaksiVerified(Transaksi $transaksi): void
    {
        $transaksi->loadMissing('pengepul.telegramAccount', 'toko', 'details.limbah');
        $pengepul = $transaksi->pengepul;
        $account = $pengepul?->telegramAccount;

        if (! $account || ! $account->telegram_user_id) {
            Log::info('transaksi-verified-notify:skip-no-telegram', [
                'transaksi_id' => $transaksi->id_transaksi,
                'pengepul_id' => $pengepul->id_pengepul ?? null,
            ]);
            return;
        }

        $chatId = (int) $account->telegram_user_id;
        $kode = $transaksi->kode_transaksi ?? $transaksi->id_transaksi;
        $toko = $transaksi->toko->nama_toko ?? '-';
        $tanggal = \Carbon\Carbon::parse($transaksi->tanggal)->format('d M Y');
        $totalFormatted = number_format($transaksi->sales, 0, ',', '.');

        $lines = [];
        $lines[] = "✅ *Transaksi Terverifikasi*";
        $lines[] = "";
        $lines[] = "Transaksi Anda telah divalidasi oleh admin.";
        $lines[] = "";
        $lines[] = "Kode: `{$kode}`";
        $lines[] = "Toko: {$toko}";
        $lines[] = "Tanggal: {$tanggal}";
        $lines[] = "";
        $lines[] = "*Rincian Limbah:*";

        // Detail per limbah
        foreach ($transaksi->details as $detail) {
            $namaLimbah = $detail->limbah->nama_limbah ?? "ID {$detail->id_limbah}";
            $jumlah = (int) $detail->jumlah;
            $harga = (int) $detail->harga_saat_transaksi;
            $subtotal = $jumlah * $harga;
            
            $lines[] = "• {$namaLimbah}";
            $lines[] = "  {$jumlah} x Rp " . number_format($harga, 0, ',', '.') . " = Rp " . number_format($subtotal, 0, ',', '.');
        }

        $lines[] = "";
        $lines[] = "*Total Pickup:* {$transaksi->total_pickup}";
        $lines[] = "*Total Harga:* Rp {$totalFormatted}";
        $lines[] = "Status: ✅ *Selesai*";
        $lines[] = "";
        $lines[] = "_Terima kasih atas kontribusi Anda!_";

        $message = implode("\n", $lines);

        Log::info('transaksi-verified-notify:send', [
            'transaksi_id' => $transaksi->id_transaksi,
            'chat_id' => $chatId,
            'pengepul_id' => $pengepul->id_pengepul,
        ]);

        TelegramBotService::sendMessage($chatId, $message);
    }
}
