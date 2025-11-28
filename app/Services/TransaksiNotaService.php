<?php

namespace App\Services;

use App\Models\tb_transaksi as Transaksi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class TransaksiNotaService
{
    /**
     * Generate a single-transaction nota PDF.
     *
     * @return string|null Full filesystem path to the generated PDF, or null if PDF support is unavailable.
     */
    public static function generatePdf(Transaksi $transaksi): ?string
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return null;
        }

        $transaksi->loadMissing(['toko', 'pengepul', 'details.limbah']);

        $html = self::buildHtml($transaksi);
        $filename = self::buildFilename($transaksi) . '.pdf';

        $relativePath = 'notas/' . $filename;

        // Menggunakan ukuran A5 dan orientasi portrait
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a5', 'portrait');
        
        // Simpan menggunakan Storage supaya folder dibuat otomatis jika belum ada.
        Storage::disk('local')->put($relativePath, $pdf->output());

        // Root disk "local" ada di storage/app/private, jadi path fisik:
        $root = config('filesystems.disks.local.root', storage_path('app/private'));

        return $root . DIRECTORY_SEPARATOR . $relativePath;
    }

    protected static function buildFilename(Transaksi $transaksi): string
    {
        $kode = (string) ($transaksi->kode_transaksi ?? $transaksi->id_transaksi);
        $tokoKode = (string) ($transaksi->toko->kode_toko ?? 'toko');

        return 'nota-' . $tokoKode . '-' . $kode;
    }

    protected static function buildHtml(Transaksi $transaksi): string
    {
        $currency = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');

        $toko = $transaksi->toko;
        $pengepul = $transaksi->pengepul;

        $tanggal = Carbon::parse($transaksi->tanggal)->format('d F Y');
        $kodeTransaksi = (string) ($transaksi->kode_transaksi ?? $transaksi->id_transaksi);

        $rowsHtml = '';
        foreach ($transaksi->details as $detail) {
            $namaLimbah = $detail->limbah->nama_limbah ?? '-';
            $jumlah = (int) ($detail->jumlah ?? 0);
            $harga = (int) ($detail->harga_saat_transaksi ?? 0);
            $subtotal = $jumlah * $harga;

            $rowsHtml .= '<tr>'
                . '<td style="padding: 8px 10px;">' . htmlspecialchars($namaLimbah) . '</td>'
                . '<td style="padding: 8px 10px; text-align: right;">' . $jumlah . '</td>'
                . '<td style="padding: 8px 10px; text-align: right;">' . $currency($harga) . '</td>'
                . '<td style="padding: 8px 10px; text-align: right; font-weight: 500;">' . $currency($subtotal) . '</td>'
                . '</tr>';
        }

        if ($rowsHtml === '') {
            $rowsHtml = '<tr><td colspan="4" style="padding:10px;text-align:center;color:#666;">Tidak ada detail limbah yang tercatat dalam transaksi ini.</td></tr>';
        }

        $total = (float) ($transaksi->sales ?? 0);

        $tokoNama = $toko->nama_toko ?? 'Toko Tidak Dikenal';
        $tokoAlamat = $toko->alamat ?? '-';
        $tokoTelp = $toko->nomor_telepon ?? '-';

        $pengepulNama = $pengepul->nama ?? 'Pengepul Tidak Dikenal';
        $pengepulKendaraan = $pengepul->nomor_kendaraan ?? '-';

        // Styling CSS yang dimodernisasi
        $css = '
            body { font-family: Arial, sans-serif; color: #333; padding: 20px; font-size: 10px; margin: 0;}
            .header { background-color: #0b4f6c; color: #fff; padding: 15px 20px; margin: -20px -20px 20px -20px; border-bottom: 5px solid #2ecc71; }
            .header h1 { font-size: 18px; margin: 0; font-weight: bold; }
            .header p { font-size: 10px; margin-top: 5px; }
            .info-grid { display: block; overflow: auto; margin-bottom: 20px; }
            .col { float: left; width: 50%; box-sizing: border-box; }
            .info-box { margin-bottom: 15px; }
            .info-box h2 { font-size: 12px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin: 0 0 8px 0; color: #0b4f6c; }
            .info-box div { margin-bottom: 2px; line-height: 1.4; }
            strong { font-weight: bold; width: 70px; display: inline-block; }
            
            /* Table Styling */
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th, td { font-size: 10px; border: none; }
            th { background: #f2f2f2; text-align: left; padding: 10px; font-weight: bold; border-bottom: 2px solid #ddd; }
            td { border-bottom: 1px solid #eee; padding: 8px 10px; }
            
            /* Footer/Total */
            tfoot tr td { border-top: 2px solid #000; font-size: 12px; background-color: #f2f2f2; }
            .total-row td:last-child { background-color: #e0e0e0; color: #000; font-weight: bold; }
            
            /* Signature Area */
            .signature-area { display: block; overflow: auto; margin-top: 40px; font-size: 10px; text-align: center; }
            .signature-col { float: left; width: 50%; }
            .signature-line { margin-top: 40px; border-top: 1px solid #333; width: 150px; display: inline-block; }
        ';


        return '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Nota Transaksi ' . htmlspecialchars($kodeTransaksi)
            . '</title><style>' . $css . '</style></head><body>'
            . '<div class="header">'
            . '<h1>NOTA TRANSAKSI LIMBAH</h1>'
            . '<p>CV. VIA BAN | ID: ' . htmlspecialchars($kodeTransaksi) . ' | Tanggal: ' . htmlspecialchars($tanggal) . '</p>'
            . '</div>'

            // Data Pengepul dan Toko berdampingan (Grid 2 kolom)
            . '<div class="info-grid">'
            . '<div class="col">'
            . '<div class="info-box">'
            . '<h2>DATA PENGECER (TOKO)</h2>'
            . '<div><strong>Nama:</strong> ' . htmlspecialchars($tokoNama) . '</div>'
            . '<div><strong>Alamat:</strong> ' . htmlspecialchars($tokoAlamat) . '</div>'
            . '<div><strong>Telepon:</strong> ' . htmlspecialchars($tokoTelp) . '</div>'
            . '</div>'
            . '</div>'
            . '<div class="col">'
            . '<div class="info-box">'
            . '<h2>DATA PENGEPUL</h2>'
            . '<div><strong>Nama:</strong> ' . htmlspecialchars($pengepulNama) . '</div>'
            . '<div><strong>No. Kendaraan:</strong> ' . htmlspecialchars($pengepulKendaraan) . '</div>'
            . '</div>'
            . '</div>'
            . '</div>' // end info-grid

            . '<h2>DETAIL LIMBAH TRANSAKSI</h2>'
            . '<table><thead><tr>'
            . '<th style="width: 45%;">Limbah</th>'
            . '<th style="text-align: right; width: 15%;">Jumlah</th>'
            . '<th style="text-align: right; width: 20%;">Harga Satuan</th>'
            . '<th style="text-align: right; width: 20%;">Subtotal</th>'
            . '</tr></thead><tbody>'
            . $rowsHtml
            . '</tbody><tfoot><tr class="total-row">'
            . '<td colspan="3" style="padding: 10px; text-align: right; font-weight: bold; background-color: #f2f2f2; border: none;">TOTAL KESELURUHAN</td>'
            . '<td style="padding: 10px; text-align: right; font-weight: bold; color: #000; border: none;">' . $currency($total) . '</td>'
            . '</tr></tfoot></table>'
            
            // Area Tanda Tangan
            . '<div class="signature-area">'
            . '<div class="signature-col">'
            . 'Pengepul<br>'
            . '(Tanda Tangan)<br>'
            . '<div class="signature-line"></div>'
            . '</div>'
            . '<div class="signature-col">'
            . 'Toko<br>'
            . '(Tanda Tangan)<br>'
            . '<div class="signature-line"></div>'
            . '</div>'
            . '</div>'

            . '</body></html>';
    }
}