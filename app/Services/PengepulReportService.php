<?php

namespace App\Services;

use App\Models\tb_pengepul as Pengepul;
use App\Models\tb_transaksi as Transaksi;
use Illuminate\Support\Carbon;
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

        [$rows, $perLimbah, $grandTotal] = self::collectRows($pengepul->id_pengepul, $start, $end);

        $bulanLabel = $start->format('F Y');
        $title = 'Laporan Pengepul ' . $pengepul->nama . ' - ' . $bulanLabel;
        $html = self::buildHtml($title, $pengepul->nama, $rows, $perLimbah, $grandTotal);
        $safeName = self::buildFilename($pengepul->nama, $start);

        return self::buildResponse($html, $safeName, $data['format'] ?? 'pdf');
    }

    /**
     * Collect flattened transaction rows and per-limbah totals.
     *
     * @return array{0: array<int, array<string, mixed>>, 1: array<string, float>, 2: float}
     */
    protected static function collectRows(int $pengepulId, Carbon $start, Carbon $end): array
    {
        $transaksis = Transaksi::query()
            ->with(['toko', 'details.limbah'])
            ->where('id_pengepul', $pengepulId)
            ->where('status', 'selesai')
            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
            ->orderBy('tanggal')
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

    protected static function buildHtml(string $title, string $pengepul, array $rows, array $perLimbah, float $grandTotal): string
    {
        $currency = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

        $summaryRows = '';
        foreach ($perLimbah as $nama => $total) {
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
        foreach ($rows as $row) {
            $detailRows .= '<tr>'
                . '<td>' . htmlspecialchars($row['tanggal']) . '</td>'
                . '<td>' . htmlspecialchars($row['toko']) . '</td>'
                . '<td>' . htmlspecialchars($row['limbah']) . '</td>'
                . '<td class="right">' . htmlspecialchars((string) $row['jumlah']) . '</td>'
                . '<td class="right">' . $currency($row['harga']) . '</td>'
                . '<td class="right">' . $currency($row['subtotal']) . '</td>'
                . '</tr>';
        }
        if ($detailRows === '') {
            $detailRows = '<tr><td colspan="6" style="padding:8px;border:1px solid #ccc;text-align:center;color:#666;">Tidak ada transaksi pada periode ini</td></tr>';
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
            . '<h2>Ringkasan per Limbah</h2><table><thead><tr><th>Limbah</th><th>Total</th></tr></thead><tbody>'
            . $summaryRows
            . '</tbody></table>'
            . '<h2>Detail Transaksi</h2><table><thead><tr>'
            . '<th>Tanggal</th><th>Toko</th><th>Limbah</th><th class="right">Jumlah</th><th class="right">Harga</th><th class="right">Subtotal</th>'
            . '</tr></thead><tbody>'
            . $detailRows
            . '</tbody><tfoot><tr><td colspan="5" class="right" style="font-weight:bold;">Grand Total</td>'
            . '<td class="right" style="font-weight:bold;">' . $currency($grandTotal) . '</td></tr></tfoot></table>'
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
}
