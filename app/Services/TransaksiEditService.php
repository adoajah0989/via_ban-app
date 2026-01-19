<?php

namespace App\Services;

use App\Models\tb_transaksi;
use App\Models\detail_transaksi;
use Illuminate\Support\Facades\DB;

class TransaksiEditService
{
    /**
     * Get pending transactions for a specific pengepul with summary info.
     *
     * @param int $pengepulId
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public static function getPendingTransactionsForPengepul(int $pengepulId, int $limit = 10)
    {
        return tb_transaksi::with(['toko', 'details.limbah'])
            ->where('id_pengepul', $pengepulId)
            ->where('status', 'pending')
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get full transaction details for editing.
     *
     * @param int $transaksiId
     * @return tb_transaksi|null
     */
    public static function getTransaksiDetailsForEdit(int $transaksiId): ?tb_transaksi
    {
        return tb_transaksi::with(['toko', 'details.limbah', 'pengepul'])
            ->find($transaksiId);
    }

    /**
     * Validate that pengepul has permission to edit this transaction.
     *
     * @param int $transaksiId
     * @param int $pengepulId
     * @return bool
     */
    public static function validateEditPermission(int $transaksiId, int $pengepulId): bool
    {
        $transaksi = tb_transaksi::find($transaksiId);

        if (! $transaksi) {
            return false;
        }

        // Only allow editing own transactions that are still pending
        return $transaksi->id_pengepul === $pengepulId 
            && $transaksi->status === 'pending';
    }

    /**
     * Update transaction with new data.
     *
     * @param int $transaksiId
     * @param array $data Expected keys: 'tanggal' (optional), 'qty_map' (optional), 'summary' (optional)
     * @return tb_transaksi|null
     */
    public static function updateTransaksi(int $transaksiId, array $data): ?tb_transaksi
    {
        $transaksi = tb_transaksi::find($transaksiId);

        if (! $transaksi) {
            return null;
        }

        DB::transaction(function () use ($transaksi, $data) {
            // Update tanggal if provided
            if (isset($data['tanggal'])) {
                $transaksi->tanggal = $data['tanggal'];
            }

            // Update items if qty_map and summary provided
            if (isset($data['qty_map']) && isset($data['summary'])) {
                // Delete old details
                $transaksi->details()->delete();

                // Update totals
                $transaksi->total_pickup = $data['summary']['total_pickup'];
                $transaksi->sales = $data['summary']['total_sales'];

                // Save transaction first
                $transaksi->save();

                // Create new details
                if (! empty($data['summary']['rows'])) {
                    $transaksi->details()->createMany($data['summary']['rows']);
                }
            } else {
                // Just save the transaction if only date changed
                $transaksi->save();
            }
        });

        return $transaksi->fresh(['toko', 'details.limbah']);
    }

    /**
     * Format transaction for display in Telegram.
     *
     * @param tb_transaksi $transaksi
     * @return string
     */
    public static function formatTransaksiForDisplay(tb_transaksi $transaksi): string
    {
        $lines = [];
        $lines[] = "Toko: {$transaksi->toko->nama_toko} ({$transaksi->toko->kode_toko})";
        $lines[] = "Tanggal: " . \Carbon\Carbon::parse($transaksi->tanggal)->format('d M Y');
        $lines[] = "Kode: {$transaksi->kode_transaksi}";
        $lines[] = "Status: {$transaksi->status}";
        $lines[] = "";
        $lines[] = "Rincian:";

        foreach ($transaksi->details as $detail) {
            $namaLimbah = $detail->limbah->nama_limbah ?? "ID {$detail->id_limbah}";
            $subtotal = $detail->jumlah * $detail->harga_saat_transaksi;
            $lines[] = "- {$namaLimbah}: {$detail->jumlah} x " 
                . number_format($detail->harga_saat_transaksi, 0, ',', '.') 
                . ' = ' . number_format($subtotal, 0, ',', '.');
        }

        $lines[] = "";
        $lines[] = "Total pickup: {$transaksi->total_pickup}";
        $lines[] = "Total nominal: Rp " . number_format($transaksi->sales, 0, ',', '.');

        return implode("\n", $lines);
    }
}
