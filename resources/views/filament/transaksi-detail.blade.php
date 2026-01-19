@props(['record'])

<div class="fi-modal-content" style="display: flex; flex-direction: column; gap: 1rem;">
    {{-- Informasi Utama --}}
    <div style="border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);">
        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05);">
            <span style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px;">Informasi Transaksi</span>
        </div>
        <div style="padding: 16px; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Kode Transaksi</p>
                <p style="font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.9); margin: 0;">{{ $record->kode_transaksi }}</p>
            </div>
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Tanggal</p>
                <p style="font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.9); margin: 0;">{{ $record->tanggal ? \Carbon\Carbon::parse($record->tanggal)->format('d M Y') : '-' }}</p>
            </div>
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Toko</p>
                <p style="font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.9); margin: 0;">{{ $record->toko->nama_toko ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Kode Toko</p>
                <p style="font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.9); margin: 0;">{{ $record->toko->kode_toko ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Pengepul</p>
                <p style="font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.9); margin: 0;">{{ $record->pengepul->nama ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Status</p>
                @php
                    $statusStyle = match($record->status) {
                        'pending' => 'background: rgba(251, 191, 36, 0.2); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3);',
                        'selesai' => 'background: rgba(34, 197, 94, 0.2); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3);',
                        'batal' => 'background: rgba(239, 68, 68, 0.2); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);',
                        default => 'background: rgba(255,255,255,0.1); color: rgba(255,255,255,0.7);',
                    };
                @endphp
                <span style="display: inline-flex; padding: 3px 10px; border-radius: 6px; font-size: 11px; font-weight: 500; {{ $statusStyle }}">
                    {{ ucfirst($record->status) }}
                </span>
            </div>
        </div>
    </div>

    {{-- Detail Limbah --}}
    <div style="border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);">
        <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05);">
            <span style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px;">Detail Limbah</span>
        </div>
        {{-- Card-based layout untuk mobile --}}
        <div style="padding: 12px; display: flex; flex-direction: column; gap: 8px;">
            @foreach($record->details as $detail)
            <div style="padding: 12px; border-radius: 6px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size: 13px; font-weight: 500; color: rgba(255,255,255,0.9);">{{ $detail->limbah->nama_limbah ?? '-' }}</span>
                    <span style="font-size: 12px; color: rgba(255,255,255,0.5);">× {{ $detail->jumlah }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 11px; color: rgba(255,255,255,0.5);">@ Rp {{ number_format($detail->harga_saat_transaksi, 0, ',', '.') }}</span>
                    <span style="font-size: 13px; font-weight: 600; color: #22c55e;">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Ringkasan --}}
    <div style="border-radius: 8px; padding: 16px; border: 1px solid rgba(34, 197, 94, 0.2); background: rgba(34, 197, 94, 0.1);">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div>
                <p style="font-size: 10px; color: rgba(34, 197, 94, 0.8); margin: 0 0 4px 0; text-transform: uppercase; font-weight: 500;">Total Pickup</p>
                <p style="font-size: 20px; font-weight: 600; color: #22c55e; margin: 0;">{{ $record->total_pickup }}</p>
            </div>
            <div style="text-align: right;">
                <p style="font-size: 10px; color: rgba(34, 197, 94, 0.8); margin: 0 0 4px 0; text-transform: uppercase; font-weight: 500;">Total Nominal</p>
                <p style="font-size: 20px; font-weight: 600; color: #22c55e; margin: 0;">Rp {{ number_format($record->sales, 0, ',', '.') }}</p>
            </div>
        </div>
    </div>

    {{-- Waktu --}}
    <div style="border-radius: 8px; padding: 12px 16px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Dibuat</p>
                <p style="font-size: 11px; color: rgba(255,255,255,0.7); margin: 0;">{{ $record->created_at ? $record->created_at->format('d M Y, H:i') : '-' }}</p>
            </div>
            <div>
                <p style="font-size: 10px; color: rgba(255,255,255,0.5); margin: 0 0 4px 0;">Diperbarui</p>
                <p style="font-size: 11px; color: rgba(255,255,255,0.7); margin: 0;">{{ $record->updated_at ? $record->updated_at->format('d M Y, H:i') : '-' }}</p>
            </div>
        </div>
    </div>
</div>
