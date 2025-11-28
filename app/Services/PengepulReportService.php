<?php

namespace App\Services;

use App\Models\tb_pengepul as Pengepul;
use App\Models\tb_laporan_pengepul;
use App\Models\tb_transaksi as Transaksi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PengepulReportService
{
    /**
     * Generate a pengepul report download response.
     */
    public static function download(array $data): StreamedResponse
    {
        $pengepul = Pengepul::findOrFail($data['pengepul_id']);
        $date = Carbon::parse($data['bulan']);
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        [$rowsSelesai, $perLimbahSelesai, $grandSelesai] = self::collectRowsForStatus($pengepul->id_pengepul, $start, $end, 'selesai');
        [$rowsPending, , $grandPending] = self::collectRowsForStatus($pengepul->id_pengepul, $start, $end, 'pending');

        $bulanLabel = $start->format('F Y');
        $title = 'Laporan Pengepul ' . $pengepul->nama . ' - ' . $bulanLabel;
        $html = self::buildHtml($title, $pengepul->nama, $rowsSelesai, $perLimbahSelesai, $grandSelesai, $rowsPending, $grandPending);
        $safeName = self::buildFilename($pengepul->nama, $start);

        $format = strtolower($data['format'] ?? 'pdf');

        // Jika PDF dan DomPDF tersedia, simpan salinan file + catat ke tabel tb_laporan_pengepul.
        if ($format === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');

            $relativePath = 'laporan_pengepul/' . $safeName . '.pdf';
            Storage::disk('local')->put($relativePath, $pdf->output());

            if (Schema::hasTable('tb_laporan_pengepul')) {
                tb_laporan_pengepul::create([
                    'id_pengepul' => $pengepul->id_pengepul,
                    'bulan' => $start->toDateString(),
                    'format' => 'pdf',
                    'path' => $relativePath,
                    'grand_total' => $grandSelesai,
                ]);
            }

            return response()->streamDownload(
                static fn () => print($pdf->stream()),
                $safeName . '.pdf'
            );
        }

        // Untuk HTML atau jika DomPDF tidak tersedia, tetap gunakan path streaming biasa.
        return self::buildResponse($html, $safeName, $format);
    }

    /**
     * Collect flattened transaction rows and per-limbah totals for a single status.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, float>, 2: float}
     */
    protected static function collectRowsForStatus(int $pengepulId, Carbon $start, Carbon $end, string $status): array
    {
        $transaksis = Transaksi::query()
            ->with(['toko', 'details.limbah'])
            ->where('id_pengepul', $pengepulId)
            ->where('status', $status)
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->orderBy('tanggal')
            ->orderBy('kode_transaksi')
            ->get();

        $rows = [];
        $grandTotal = 0.0;
        $perLimbah = [];

        foreach ($transaksis as $trx) {
            foreach ($trx->details as $detail) {
                $limbahNama = $detail->limbah->nama_limbah ?? '-';
                $jumlah = (float) ($detail->jumlah ?? 0);
                $harga = (float) ($detail->harga_saat_transaksi ?? 0);
                $subtotal = $jumlah * $harga;

                $grandTotal += $subtotal;
                $perLimbah[$limbahNama] = ($perLimbah[$limbahNama] ?? 0) + $subtotal;

                $rows[] = [
                    'tanggal' => Carbon::parse($trx->tanggal)->format('d M Y'),
                    'kode_transaksi' => $trx->kode_transaksi ?? str_pad((string) $trx->id_transaksi, 8, '0', STR_PAD_LEFT),
                    'toko' => $trx->toko->nama_toko ?? '-',
                    'limbah' => $limbahNama,
                    'jumlah' => $detail->jumlah,
                    'harga' => $detail->harga_saat_transaksi,
                    'subtotal' => $subtotal,
                ];
            }
        }

        return [$rows, $perLimbah, $grandTotal];
    }

    protected static function buildHtml(
        string $title,
        string $pengepul,
        array $rowsSelesai,
        array $perLimbahSelesai,
        float $grandSelesai,
        array $rowsPending,
        float $grandPending
    ): string
    {
        $currency = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

        $summaryRows = '';
        foreach ($perLimbahSelesai as $nama => $total) {
            $summaryRows .= '<tr><td style="padding:6px 8px;border:1px solid #ccc;">'
                . htmlspecialchars($nama)
                . '</td><td style="padding:6px 8px;border:1px solid #ccc;text-align:right;">'
                . $currency($total)
                . '</td></tr>';
        }
        if ($summaryRows === '') {
            $summaryRows = '<tr><td colspan="2" style="padding:6px 8px;border:1px solid #ccc;text-align:center;color:#666;">Tidak ada data</td></tr>';
        }

        $detailRows = '';
        $lastKode = null;

        foreach ($rowsSelesai as $row) {
            $kode = $row['kode_transaksi'] ?? '';
            $showHeader = $kode !== $lastKode;

            $detailRows .= '<tr>'
                . '<td>' . ($showHeader ? htmlspecialchars($row['tanggal']) : '') . '</td>'
                . '<td>' . ($showHeader ? htmlspecialchars($row['kode_transaksi']) : '') . '</td>'
                . '<td>' . ($showHeader ? htmlspecialchars($row['toko']) : '') . '</td>'
                . '<td>' . htmlspecialchars($row['limbah']) . '</td>'
                . '<td class="right">' . htmlspecialchars((string) $row['jumlah']) . '</td>'
                . '<td class="right">' . $currency($row['harga']) . '</td>'
                . '<td class="right">' . $currency($row['subtotal']) . '</td>'
                . '</tr>';

            $lastKode = $kode;
        }

        if ($detailRows === '') {
            $detailRows = '<tr><td colspan="7" style="padding:8px;border:1px solid #ccc;text-align:center;color:#666;">Tidak ada transaksi selesai pada periode ini</td></tr>';
        }

        // Pending rows
        $pendingRowsHtml = '';
        $lastKodePending = null;

        foreach ($rowsPending as $row) {
            $kode = $row['kode_transaksi'] ?? '';
            $showHeader = $kode !== $lastKodePending;

            $pendingRowsHtml .= '<tr>'
                . '<td>' . ($showHeader ? htmlspecialchars($row['tanggal']) : '') . '</td>'
                . '<td>' . ($showHeader ? htmlspecialchars($row['kode_transaksi']) : '') . '</td>'
                . '<td>' . ($showHeader ? htmlspecialchars($row['toko']) : '') . '</td>'
                . '<td>' . htmlspecialchars($row['limbah']) . '</td>'
                . '<td class="right">' . htmlspecialchars((string) $row['jumlah']) . '</td>'
                . '<td class="right">' . $currency($row['harga']) . '</td>'
                . '<td class="right">' . $currency($row['subtotal']) . '</td>'
                . '</tr>';

            $lastKodePending = $kode;
        }

        if ($pendingRowsHtml === '') {
            $pendingRowsHtml = '<tr><td colspan="7" style="padding:8px;border:1px solid #ccc;text-align:center;color:#666;">Tidak ada transaksi pending pada periode ini</td></tr>';
        }

        $printedAt = htmlspecialchars(Carbon::now()->format('d M Y H:i'));

        return '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>'
            . htmlspecialchars($title)
            . '</title><style>body{font-family:\"Segoe UI\",Tahoma,Arial,sans-serif;color:#111;padding:32px;}'
            . 'h1{margin-bottom:4px;}h2{margin:24px 0 12px;font-size:16px;}table{width:100%;border-collapse:collapse;font-size:13px;}'
            . 'th,td{border:1px solid #ccc;padding:8px;}th{background:#f4f4f4;text-align:left;}td.right{text-align:right;}'
            . '.header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;}'
            . '.muted{color:#6b7280;font-size:12px;}.signature td{border:none;padding-top:40px;text-align:center;}'
            . '.signature .line{margin-top:40px;border-top:1px solid #999;padding-top:8px;}</style></head><body>'
            . '<div class="header"><div><h1>' . htmlspecialchars($title) . '</h1>'
            . '<div class="muted">Pengepul: ' . htmlspecialchars($pengepul) . '</div></div>'
            . '<div class="muted">Dicetak: ' . $printedAt . '</div></div>'
            . '<h2>Ringkasan per Limbah (Transaksi Selesai)</h2><table><thead><tr><th>Limbah</th><th>Total</th></tr></thead><tbody>'
            . $summaryRows
            . '</tbody></table>'
            . '<h2>Detail Transaksi Selesai</h2><table><thead><tr>'
            . '<th>Tanggal</th><th>ID Transaksi</th><th>Toko</th><th>Limbah</th><th class="right">Jumlah</th><th class="right">Harga</th><th class="right">Subtotal</th>'
            . '</tr></thead><tbody>'
            . $detailRows
            . '</tbody><tfoot><tr><td colspan="6" class="right" style="font-weight:bold;">Grand Total Transaksi Selesai</td>'
            . '<td class="right" style="font-weight:bold;">' . $currency($grandSelesai) . '</td></tr></tfoot></table>'
            . '<h2>Detail Transaksi Pending</h2><table><thead><tr>'
            . '<th>Tanggal</th><th>ID Transaksi</th><th>Toko</th><th>Limbah</th><th class="right">Jumlah</th><th class="right">Harga</th><th class="right">Subtotal</th>'
            . '</tr></thead><tbody>'
            . $pendingRowsHtml
            . '</tbody><tfoot><tr><td colspan="6" class="right" style="font-weight:bold;">Grand Total Transaksi Pending</td>'
            . '<td class="right" style="font-weight:bold;">' . $currency($grandPending) . '</td></tr></tfoot></table>'
            . '<table class="signature" style="margin-top:48px;"><tr>'
            . '<td><div class="who">Pengepul</div><div class="line">Tanda Tangan</div></td>'
            . '<td><div class="who">Mengetahui</div><div class="line">Tanda Tangan</div></td>'
            . '</tr></table></body></html>';
    }

    protected static function buildFilename(string $pengepul, Carbon $start): string
    {
        $slug = Str::slug($pengepul) ?: 'pengepul';

        return 'laporan-pengepul-' . $slug . '-' . $start->format('Y-m');
    }

    protected static function buildResponse(string $html, string $safeName, string $format): StreamedResponse
    {
        $format = strtolower($format);

        if ($format === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');

            return response()->streamDownload(
                static fn () => print($pdf->stream()),
                $safeName . '.pdf'
            );
        }

        return response()->streamDownload(
            static fn () => print($html),
            $safeName . '.html',
            ['Content-Type' => 'text/html; charset=UTF-8']
        );
    }

    /**
     * Generate and store a monthly PDF report for a pengepul,
     * returning the absolute file path for use by Telegram bot.
     *
     * Returns null if DomPDF is not available or pengepul not found.
     */
    public static function generateMonthlyPdfForTelegram(int $pengepulId, Carbon $month): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        $pengepul = Pengepul::find($pengepulId);
        if (! $pengepul) {
            return null;
        }

        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        [$rowsSelesai, $perLimbahSelesai, $grandSelesai] = self::collectRowsForStatus($pengepul->id_pengepul, $start, $end, 'selesai');
        [$rowsPending, , $grandPending] = self::collectRowsForStatus($pengepul->id_pengepul, $start, $end, 'pending');

        $bulanLabel = $start->format('F Y');
        $title = 'Laporan Pengepul ' . $pengepul->nama . ' - ' . $bulanLabel;
        $html = self::buildHtml($title, $pengepul->nama, $rowsSelesai, $perLimbahSelesai, $grandSelesai, $rowsPending, $grandPending);
        $safeName = self::buildFilename($pengepul->nama, $start) . '-bot';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        $relativePath = 'laporan_pengepul/' . $safeName . '.pdf';
        Storage::disk('local')->put($relativePath, $pdf->output());

        if (Schema::hasTable('tb_laporan_pengepul')) {
            tb_laporan_pengepul::create([
                'id_pengepul' => $pengepul->id_pengepul,
                'bulan' => $start->toDateString(),
                'format' => 'pdf',
                'path' => $relativePath,
                'grand_total' => $grandSelesai,
            ]);
        }

        return Storage::disk('local')->path($relativePath);
    }
}
