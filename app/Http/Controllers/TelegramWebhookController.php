<?php

namespace App\Http\Controllers;

use App\Models\tb_limbah;
use App\Models\tb_toko;
use App\Models\tb_telegram_user;
use App\Models\tb_transaksi as Transaksi;
use App\Services\TelegramBotService;
use App\Services\TelegramUserResolver;
use App\Services\TransaksiDetailService;
use App\Services\PengepulSummaryService;
use App\Services\PengepulReportService;
use App\Services\TransaksiNotificationService;
use App\Services\TelegramSessionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $update = $request->all();
        Log::info('telegram-webhook', $update);

        // 1) Callback query (inline keyboard)
        if (isset($update['callback_query'])) {
            $callback = $update['callback_query'];
            $from = $callback['from'] ?? [];
            $telegramUserId = (int) ($from['id'] ?? 0);
            $chatId = (int) ($callback['message']['chat']['id'] ?? 0);
            $data = (string) ($callback['data'] ?? '');

            $identity = TelegramUserResolver::resolveByTelegramId($telegramUserId);

            if ($data === 'pengepul_menu_summary') {
                if ($identity['role'] !== 'pengepul' || ! $identity['pengepul_id']) {
                    TelegramBotService::sendMessage($chatId, 'Ringkasan hanya tersedia untuk akun pengepul.');
                } else {
                    $summary = PengepulSummaryService::buildSummaryForPengepul($identity['pengepul_id']);

                    $lines = [];
                    $lines[] = 'Ringkasan transaksi Anda:';
                    $lines[] = '';
                    $lines[] = 'transaksi pending : ' . $summary['pending'];
                    $lines[] = 'transaksi selesai : ' . $summary['selesai'];
                    $lines[] = 'toko yang terinput : ' . $summary['toko_total'];
                    $lines[] = '';
                    $lines[] = 'total tagihan : Rp ' . number_format($summary['total_tagihan'], 0, ',', '.');

                    TelegramBotService::sendMessage($chatId, implode("\n", $lines));
                }
            } elseif ($data === 'pengepul_menu_transaksi') {
                // Minta kode toko terlebih dahulu sebelum menjelaskan format TRX.
                TelegramSessionService::set($telegramUserId, 'wait_kode_toko_for_trx_help');
                TelegramBotService::sendMessage(
                    $chatId,
                    "Silakan kirim *kode toko* terlebih dahulu.\n"
                    . "Contoh: `JKT003`"
                );
            } elseif ($data === 'admin_menu_validate') {
                TelegramBotService::sendMessage(
                    $chatId,
                    "Fitur validasi transaksi via bot sedang disiapkan."
                );
            } elseif ($data === 'trx_confirm_ok') {
                $this->finalizeTrxFromSession($chatId, $telegramUserId, $identity);
            } elseif ($data === 'trx_confirm_cancel') {
                TelegramSessionService::clear($telegramUserId);
                TelegramBotService::sendMessage($chatId, "Input transaksi dibatalkan.");
                TelegramBotService::sendPengepulMainMenu($chatId);
            }

            return response()->noContent();
        }

        // 2) Pesan teks biasa
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

        // Normalisasi teks untuk command (mis. "/start@BotName arg" -> "/start").
        $command = $this->extractCommand($text);
        $state = TelegramSessionService::getState($telegramUserId);

        // Command khusus /trx untuk memulai alur input transaksi via format satu pesan.
        if ($command === '/trx') {
            TelegramSessionService::set($telegramUserId, 'wait_kode_toko_for_trx_help');
            TelegramBotService::sendMessage(
                $chatId,
                "Mode input transaksi singkat.\n"
                . "Silakan kirim *kode toko* terlebih dahulu.\n"
                . "Contoh: JKT003"
            );

            return response()->noContent();
        }

        // Perintah admin untuk mengirim laporan ringkas ke semua pengepul aktif.
        if ($command === '/kirim_laporan') {
            if ($identity['role'] !== 'admin') {
                TelegramBotService::sendMessage($chatId, 'Perintah ini hanya bisa dipakai oleh admin.');
                return response()->noContent();
            }

            $start = Carbon::now()->startOfMonth();
            $end = Carbon::now()->endOfMonth();
            $bulanLabel = $start->format('F Y');

            $accounts = tb_telegram_user::with('pengepul')
                ->where('role', 'pengepul')
                ->where('is_active', true)
                ->whereNotNull('telegram_user_id')
                ->get();

            $sentCount = 0;

            foreach ($accounts as $acc) {
                if (! $acc->pengepul) {
                    continue;
                }

                $summary = PengepulSummaryService::buildSummaryForPengepulInRange(
                    $acc->id_pengepul,
                    $start,
                    $end
                );

                if ($summary['pending_count'] === 0 && $summary['selesai_count'] === 0) {
                    continue;
                }

                $currency = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

                $lines = [];
                $lines[] = 'Laporan bulan ' . $bulanLabel;
                $lines[] = 'Pengepul: ' . ($acc->pengepul->nama ?? '-');
                $lines[] = '';
                $lines[] = 'transaksi pending : ' . $summary['pending_count'] . ' (' . $currency($summary['pending_total']) . ')';
                $lines[] = 'transaksi selesai : ' . $summary['selesai_count'] . ' (' . $currency($summary['selesai_total']) . ')';
                $lines[] = 'toko yang terinput : ' . $summary['toko_total'];
                $lines[] = '';
                $lines[] = 'total tagihan : ' . $currency($summary['selesai_total']);

                TelegramBotService::sendMessage((int) $acc->telegram_user_id, implode("\n", $lines));

                // Kirim juga file laporan bulanan dalam format PDF, jika tersedia.
                $pdfPath = PengepulReportService::generateMonthlyPdfForTelegram($acc->id_pengepul, $start);
                if ($pdfPath) {
                    TelegramBotService::sendDocumentFromPath(
                        (int) $acc->telegram_user_id,
                        $pdfPath,
                        'Laporan transaksi bulan ' . $bulanLabel
                    );
                }

                $sentCount++;
            }

            TelegramBotService::sendMessage(
                $chatId,
                'Laporan telah dikirim ke ' . $sentCount . ' pengepul untuk bulan ' . $bulanLabel . '.'
            );

            return response()->noContent();
        }

        // Jika sedang diminta kode toko untuk bantuan format TRX.
        if (
            $state === 'wait_kode_toko_for_trx_help'
            && $command === null
            && ! str_starts_with(strtoupper($text), 'TRX ')
        ) {
            $this->handleTrxHelpForToko($chatId, $telegramUserId, $text);
            return response()->noContent();
        }

        // Shortcut: format input satu pesan, misal "TRX 11-28 1:10 2:5".
        if (str_starts_with(strtoupper($text), 'TRX ')) {
            if ($state === 'wait_trx_for_toko') {
                $this->handleTrxShortcutForKnownToko($chatId, $telegramUserId, $text, $identity);
            } else {
                $this->handleTrxShortcut($chatId, $telegramUserId, $text, $identity);
            }

            return response()->noContent();
        }

        // Bantuan format input.
        if ($command === '/format_input') {
            $this->sendFormatHelp($chatId);
            return response()->noContent();
        }

        // /start: buka menu utama sesuai role.
        if ($command === '/start') {
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

        // Jika ada command lain yang tidak dikenali (diawali "/"), beri pesan khusus.
        if ($command !== null) {
            TelegramBotService::sendMessage(
                $chatId,
                "Perintah *{$command}* tidak dikenali.\n"
                . "Command yang tersedia:\n"
                . "- /start\n"
                . "- /format_input\n"
                . "- /trx (input transaksi)\n"
                . "- /kirim_laporan (admin)\n\n"
                . "Untuk input transaksi, pilih menu transaksi atau gunakan command /trx."
            );
            return response()->noContent();
        }

        // Default: balasan sederhana berdasarkan role.
        if ($identity['role'] === 'pengepul') {
            TelegramBotService::sendMessage(
                $chatId,
                "Perintah belum dikenali.\nGunakan /start untuk membuka menu atau /format_input untuk contoh input TRX."
            );
        } elseif ($identity['role'] === 'admin') {
            TelegramBotService::sendMessage(
                $chatId,
                "Perintah belum dikenali.\nGunakan /start untuk membuka menu admin."
            );
        } else {
            TelegramBotService::sendMessage(
                $chatId,
                "Akun belum terdaftar. Gunakan /start lalu hubungi admin."
            );
        }

        return response()->noContent();
    }

    /**
     * Kirim bantuan format input TRX dan daftar ID limbah.
     */
    protected function sendFormatHelp(int $chatId): void
    {
        $lines = [];
        $lines[] = "*Format input transaksi (1 pesan)*";
        $lines[] = "";
        $lines[] = "`TRX <KODE_TOKO> <MM-DD> <ID_LIMBAH1>:<JUMLAH1> <ID_LIMBAH2>:<JUMLAH2> ...`";
        $lines[] = "";
        $lines[] = "Contoh:";
        $lines[] = "`TRX JKT003 11-28 1:10 2:5 3:0`";
        $lines[] = "";
        $lines[] = "Tanggal akan memakai *tahun saat ini*.";
        $lines[] = "";
        $lines[] = "Daftar ID limbah:";

        $limbah = tb_limbah::query()
            ->orderBy('nama_limbah')
            ->get(['id_limbah', 'nama_limbah']);

        foreach ($limbah as $row) {
            $lines[] = "- {$row->id_limbah}: {$row->nama_limbah}";
        }

        TelegramBotService::sendMessage($chatId, implode("\n", $lines));
    }

    /**
     * Terima kode toko, lalu kirimkan bantuan format input TRX
     * yang sudah berisi contoh untuk toko tersebut.
     */
    protected function handleTrxHelpForToko(int $chatId, int $telegramUserId, string $text): void
    {
        $kodeToko = strtoupper(strtok(trim($text), ' '));

        $toko = tb_toko::with('pusat')->where('kode_toko', $kodeToko)->first();
        if (! $toko) {
            TelegramBotService::sendMessage(
                $chatId,
                "Kode toko *{$kodeToko}* tidak ditemukan.\n"
                . "Silakan kirim ulang kode toko yang benar (contoh: `JKT003`) atau kirim /start untuk kembali ke menu."
            );

            return;
        }

        // Simpan toko di session dan minta user mengirim format TRX tanpa kode toko.
        TelegramSessionService::set($telegramUserId, 'wait_trx_for_toko', [
            'toko_id' => $toko->id_toko,
            'kode_toko' => $toko->kode_toko,
            'nama_toko' => $toko->nama_toko,
            'id_pusat' => $toko->id_pusat,
            'kode_wilayah' => $toko->kode_wilayah,
        ]);

        $lines = [];
        $lines[] = "*Format input transaksi untuk toko:*";
        $lines[] = "{$toko->nama_toko} ({$toko->kode_toko})";
        $lines[] = "";
        $lines[] = "Kirim pesan dengan format:";
        $lines[] = "`TRX <MM-DD> <ID_LIMBAH1>:<JUMLAH1> <ID_LIMBAH2>:<JUMLAH2> ...`";
        $lines[] = "";
        $lines[] = "Contoh:";
        $lines[] = "`TRX 11-28 1:10 2:5 3:0`";
        $lines[] = "";
        $lines[] = "Tanggal akan memakai *tahun saat ini*.";
        $lines[] = "";
        $lines[] = "Daftar ID limbah yang tersedia untuk pusat toko ini:";

        $limbah = tb_limbah::query()
            ->where('id_pusat', $toko->id_pusat)
            ->orderBy('nama_limbah')
            ->get(['id_limbah', 'nama_limbah']);

        if ($limbah->isEmpty()) {
            $lines[] = "_Belum ada master limbah untuk pusat toko ini._";
            TelegramBotService::sendMessage($chatId, implode("\n", $lines));
            return;
        }

        foreach ($limbah as $row) {
            $lines[] = "- {$row->id_limbah}: {$row->nama_limbah}";
        }

        $lines[] = "";
        $lines[] = "Setelah menyalin dan mengubah jumlah sesuai nota fisik,\n"
            . "kirim pesan TRX di atas untuk mendapatkan preview dan konfirmasi.";

        TelegramBotService::sendMessage($chatId, implode("\n", $lines));
    }

    /**
     * Tangani format input singkat TRX JKT003 11-28 1:10 2:5 3:0.
     */
    protected function handleTrxShortcut(
        int $chatId,
        int $telegramUserId,
        string $text,
        array $identity
    ): void {
        if ($identity['role'] !== 'pengepul') {
            TelegramBotService::sendMessage($chatId, "Format TRX hanya berlaku untuk akun pengepul.");
            return;
        }

        // TRX <KODE_TOKO> <MM-DD> <ID:QTY>...
        $pattern = '/^TRX\s+(\S+)\s+(\d{1,2})-(\d{1,2})\s+(.+)$/i';
        if (! preg_match($pattern, $text, $m)) {
            TelegramBotService::sendMessage(
                $chatId,
                "Format TRX tidak dikenali.\n"
                . "Jika Anda memakai alur /trx (toko dipilih dulu), gunakan format tanpa kode toko, misalnya:\n"
                . "`TRX 11-28 1:10 2:5 3:0`\n\n"
                . "Jika ingin tetap kirim lengkap satu pesan, gunakan format:\n"
                . "`TRX JKT003 11-28 1:10 2:5 3:0`"
            );
            return;
        }

        $kodeToko = strtoupper($m[1]);
        $mm = (int) $m[2];
        $dd = (int) $m[3];
        $itemsPart = trim($m[4]);

        try {
            $year = now()->year;
            $tanggal = Carbon::createFromDate($year, $mm, $dd)->toDateString();
        } catch (\Throwable $e) {
            TelegramBotService::sendMessage(
                $chatId,
                "Tanggal tidak valid. Gunakan format `MM-DD`, contoh: `11-28`."
            );
            return;
        }

        $toko = tb_toko::with('pusat')->where('kode_toko', $kodeToko)->first();
        if (! $toko) {
            TelegramBotService::sendMessage(
                $chatId,
                "Kode toko *{$kodeToko}* tidak ditemukan."
            );
            return;
        }

        $tokens = preg_split('/\s+/', $itemsPart);
        $qtyMap = [];
        foreach ($tokens as $token) {
            if (! str_contains($token, ':')) {
                continue;
            }
            [$idStr, $qtyStr] = explode(':', $token, 2);
            $id = (int) $idStr;
            $qty = (int) $qtyStr;
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $qtyMap[$id] = ($qtyMap[$id] ?? 0) + $qty;
        }

        if (empty($qtyMap)) {
            TelegramBotService::sendMessage(
                $chatId,
                "Tidak ada pasangan `ID:JUMLAH` yang valid ditemukan.\nContoh: `1:10 2:5`."
            );
            return;
        }

        $pengepulId = (int) ($identity['pengepul_id'] ?? 0);
        if (! $pengepulId) {
            TelegramBotService::sendMessage(
                $chatId,
                "Akun Telegram ini belum terhubung ke data pengepul."
            );
            return;
        }

        // Pastikan belum ada transaksi untuk kombinasi ini (per hari, per toko, per pengepul).
        $exists = Transaksi::query()
            ->where('id_pengepul', $pengepulId)
            ->where('id_toko', $toko->id_toko)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($exists) {
            TelegramBotService::sendMessage(
                $chatId,
                "Sudah ada transaksi untuk toko dan tanggal tersebut.\n"
                . "Gunakan menu *Edit Data* atau *Hapus Data* untuk mengubahnya."
            );
            return;
        }

        $kodeWilayah = (string) $toko->kode_wilayah;
        $idPusat = (int) ($toko->id_pusat ?? 0);

        $summary = TransaksiDetailService::summarize($qtyMap, $kodeWilayah, $idPusat);

        // Simpan ke session sementara untuk konfirmasi.
        TelegramSessionService::set($telegramUserId, 'trx_confirm', [
            'pengepul_id' => $pengepulId,
            'toko_id' => $toko->id_toko,
            'toko_nama' => $toko->nama_toko,
            'toko_kode' => $toko->kode_toko,
            'tanggal' => $tanggal,
            'kode_wilayah' => $kodeWilayah,
            'qty_map' => $qtyMap,
            'summary' => $summary,
        ]);

        $tanggalLabel = Carbon::parse($tanggal)->format('d M Y');

        $lines = [];
        $lines[] = "*Preview Transaksi (TRX)*";
        $lines[] = "Toko: {$toko->nama_toko} ({$toko->kode_toko})";
        $lines[] = "Tanggal: {$tanggalLabel}";
        $lines[] = "";
        $lines[] = "Rincian limbah:";

        foreach ($summary['rows'] as $row) {
            $id = $row['id_limbah'];
            $nama = tb_limbah::find($id)?->nama_limbah ?? ("ID {$id}");
            $jumlah = (int) $row['jumlah'];
            $harga = (int) $row['harga_saat_transaksi'];
            $subtotal = $jumlah * $harga;
            $lines[] = "- {$nama}: {$jumlah} x " . number_format($harga, 0, ',', '.') . ' = ' . number_format($subtotal, 0, ',', '.');
        }

        $lines[] = "";
        $lines[] = "*Total pickup*: {$summary['total_pickup']}";
        $lines[] = "*Total nominal*: Rp " . number_format($summary['total_sales'], 0, ',', '.');
        $lines[] = "";
        $lines[] = "Apakah data sudah benar?";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✅ Konfirmasi', 'callback_data' => 'trx_confirm_ok'],
                ],
                [
                    ['text' => '❌ Batal', 'callback_data' => 'trx_confirm_cancel'],
                ],
            ],
        ];

        TelegramBotService::sendMessageWithKeyboard($chatId, implode("\n", $lines), $keyboard);
    }

    /**
     * Tangani format TRX tanpa kode toko ketika toko sudah dipilih sebelumnya,
     * misalnya: "TRX 11-28 1:10 2:5".
     */
    protected function handleTrxShortcutForKnownToko(
        int $chatId,
        int $telegramUserId,
        string $text,
        array $identity
    ): void {
        if ($identity['role'] !== 'pengepul') {
            TelegramBotService::sendMessage($chatId, "Format TRX hanya berlaku untuk akun pengepul.");
            return;
        }

        $data = TelegramSessionService::getData($telegramUserId);
        $tokoId = (int) ($data['toko_id'] ?? 0);
        $kodeToko = (string) ($data['kode_toko'] ?? '');
        $kodeWilayah = (string) ($data['kode_wilayah'] ?? '');
        $idPusat = (int) ($data['id_pusat'] ?? 0);

        if (! $tokoId || ! $kodeToko || ! $kodeWilayah) {
            TelegramBotService::sendMessage(
                $chatId,
                "Data toko pada sesi sudah tidak lengkap.\nSilakan mulai lagi dengan perintah /trx."
            );
            TelegramSessionService::clear($telegramUserId);
            return;
        }

        // TRX <MM-DD> <ID:QTY>...
        $pattern = '/^TRX\s+(\d{1,2})-(\d{1,2})\s+(.+)$/i';
        if (! preg_match($pattern, $text, $m)) {
            TelegramBotService::sendMessage(
                $chatId,
                "Format tidak dikenali.\nContoh: `TRX 11-28 1:10 2:5 3:0`"
            );
            return;
        }

        $mm = (int) $m[1];
        $dd = (int) $m[2];
        $itemsPart = trim($m[3]);

        try {
            $year = now()->year;
            $tanggal = Carbon::createFromDate($year, $mm, $dd)->toDateString();
        } catch (\Throwable $e) {
            TelegramBotService::sendMessage(
                $chatId,
                "Tanggal tidak valid. Gunakan format `MM-DD`, contoh: `11-28`."
            );
            return;
        }

        $toko = tb_toko::with('pusat')->find($tokoId);
        if (! $toko) {
            TelegramBotService::sendMessage(
                $chatId,
                "Toko pada sesi sudah tidak ditemukan.\nSilakan mulai lagi dengan perintah /trx."
            );
            TelegramSessionService::clear($telegramUserId);
            return;
        }

        $tokens = preg_split('/\s+/', $itemsPart);
        $qtyMap = [];
        foreach ($tokens as $token) {
            if (! str_contains($token, ':')) {
                continue;
            }
            [$idStr, $qtyStr] = explode(':', $token, 2);
            $id = (int) $idStr;
            $qty = (int) $qtyStr;
            if ($id <= 0 || $qty <= 0) {
                continue;
            }
            $qtyMap[$id] = ($qtyMap[$id] ?? 0) + $qty;
        }

        if (empty($qtyMap)) {
            TelegramBotService::sendMessage(
                $chatId,
                "Tidak ada pasangan `ID:JUMLAH` yang valid ditemukan.\nContoh: `1:10 2:5`."
            );
            return;
        }

        $pengepulId = (int) ($identity['pengepul_id'] ?? 0);
        if (! $pengepulId) {
            TelegramBotService::sendMessage(
                $chatId,
                "Akun Telegram ini belum terhubung ke data pengepul."
            );
            return;
        }

        // Pastikan belum ada transaksi untuk kombinasi ini (per hari, per toko, per pengepul).
        $exists = Transaksi::query()
            ->where('id_pengepul', $pengepulId)
            ->where('id_toko', $tokoId)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($exists) {
            TelegramBotService::sendMessage(
                $chatId,
                "Sudah ada transaksi untuk toko dan tanggal tersebut.\n"
                . "Gunakan menu Edit Data atau Hapus Data untuk mengubahnya."
            );
            return;
        }

        $summary = TransaksiDetailService::summarize($qtyMap, $kodeWilayah, $idPusat);

        // Simpan ke session sementara untuk konfirmasi.
        TelegramSessionService::set($telegramUserId, 'trx_confirm', [
            'pengepul_id' => $pengepulId,
            'toko_id' => $tokoId,
            'toko_nama' => $toko->nama_toko,
            'toko_kode' => $toko->kode_toko,
            'tanggal' => $tanggal,
            'kode_wilayah' => $kodeWilayah,
            'qty_map' => $qtyMap,
            'summary' => $summary,
        ]);

        $tanggalLabel = Carbon::parse($tanggal)->format('d M Y');

        $lines = [];
        $lines[] = "Preview Transaksi (TRX)";
        $lines[] = "Toko: {$toko->nama_toko} ({$toko->kode_toko})";
        $lines[] = "Tanggal: {$tanggalLabel}";
        $lines[] = "";
        $lines[] = "Rincian limbah:";

        foreach ($summary['rows'] as $row) {
            $id = $row['id_limbah'];
            $nama = tb_limbah::find($id)?->nama_limbah ?? ("ID {$id}");
            $jumlah = (int) $row['jumlah'];
            $harga = (int) $row['harga_saat_transaksi'];
            $subtotal = $jumlah * $harga;
            $lines[] = "- {$nama}: {$jumlah} x " . number_format($harga, 0, ',', '.') . ' = ' . number_format($subtotal, 0, ',', '.');
        }

        $lines[] = "";
        $lines[] = "Total pickup: {$summary['total_pickup']}";
        $lines[] = "Total nominal: Rp " . number_format($summary['total_sales'], 0, ',', '.');
        $lines[] = "";
        $lines[] = "Apakah data sudah benar?";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => 'Konfirmasi', 'callback_data' => 'trx_confirm_ok'],
                ],
                [
                    ['text' => 'Batal', 'callback_data' => 'trx_confirm_cancel'],
                ],
            ],
        ];

        TelegramBotService::sendMessageWithKeyboard($chatId, implode("\n", $lines), $keyboard);
    }

    /**
     * Ambil command utama dari teks, misalnya:
     * "/start", "/start@NamaBot", "/start arg1" -> "/start".
     * Mengembalikan null jika bukan command (tidak diawali "/").
     */
    protected function extractCommand(string $text): ?string
    {
        $text = trim($text);
        if ($text === '' || ! str_starts_with($text, '/')) {
            return null;
        }

        // Ambil token pertama sebelum spasi.
        [$first] = explode(' ', $text, 2);
        // Hilangkan suffix "@BotName" kalau ada.
        [$cmd] = explode('@', $first, 2);

        return strtolower($cmd);
    }

    /**
     * Finalisasi transaksi setelah konfirmasi dari preview TRX.
     */
    protected function finalizeTrxFromSession(int $chatId, int $telegramUserId, array $identity): void
    {
        $data = TelegramSessionService::getData($telegramUserId);

        $pengepulId = (int) ($data['pengepul_id'] ?? 0);
        $tokoId = (int) ($data['toko_id'] ?? 0);
        $tanggal = (string) ($data['tanggal'] ?? '');
        $kodeWilayah = (string) ($data['kode_wilayah'] ?? '');
        $summary = $data['summary'] ?? null;

        if (! $pengepulId || ! $tokoId || ! $tanggal || ! $summary) {
            TelegramBotService::sendMessage($chatId, "Data transaksi tidak lengkap. Silakan kirim ulang format TRX.");
            TelegramSessionService::clear($telegramUserId);
            return;
        }

        // Pastikan belum ada transaksi untuk kombinasi ini (per hari, per toko, per pengepul).
        $exists = Transaksi::query()
            ->where('id_pengepul', $pengepulId)
            ->where('id_toko', $tokoId)
            ->whereDate('tanggal', $tanggal)
            ->exists();

        if ($exists) {
            TelegramBotService::sendMessage(
                $chatId,
                "Sudah ada transaksi untuk toko dan tanggal tersebut.\n"
                . "Gunakan menu *Edit Data* atau *Hapus Data* untuk mengubahnya."
            );
            TelegramSessionService::clear($telegramUserId);
            return;
        }

        $transaksi = Transaksi::create([
            'tanggal' => $tanggal,
            'id_toko' => $tokoId,
            'id_pengepul' => $pengepulId,
            'kode_wilayah' => $kodeWilayah,
            'total_pickup' => $summary['total_pickup'],
            'sales' => $summary['total_sales'],
        ]);

        if (! empty($summary['rows'])) {
            $transaksi->details()->createMany($summary['rows']);
        }

        // Kirim nota dan info ringkas.
        TransaksiNotificationService::sendNotaToPengepul($transaksi);

        TelegramSessionService::clear($telegramUserId);

        $toko = tb_toko::find($tokoId);
        $kode = $transaksi->kode_transaksi ?? $transaksi->id_transaksi;
        $tanggalLabel = Carbon::parse($tanggal)->format('d M Y');

        $lines = [];
        $lines[] = "Transaksi tersimpan dengan status *pending*.";
        $lines[] = "ID: `{$kode}`";
        $lines[] = "Toko: " . ($toko->nama_toko ?? '-') . " (" . ($toko->kode_toko ?? '-') . ")";
        $lines[] = "Tanggal: {$tanggalLabel}";
        $lines[] = "";
        $lines[] = "Total pickup: {$summary['total_pickup']}";
        $lines[] = "Total nominal: Rp " . number_format($summary['total_sales'], 0, ',', '.');
        $lines[] = "";
        $lines[] = "_Nota transaksi telah dikirim sebagai PDF._";

        TelegramBotService::sendMessage($chatId, implode("\n", $lines));
        TelegramBotService::sendPengepulMainMenu($chatId);
    }
}
