<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        {{-- Preview Ringkasan --}}
        @if($this->showPreview)
        <div style="border-radius: 8px; overflow: hidden; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.03);">
            <div style="padding: 12px 16px; border-bottom: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.05);">
                <span style="font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.5px;">📊 Preview Ringkasan Data</span>
            </div>
            <div style="padding: 16px;">
                {{-- Grid responsif: 2 kolom di mobile, 3 kolom di tablet, 5 kolom di desktop --}}
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div style="text-align: center; padding: 12px; border-radius: 8px; background: rgba(251, 191, 36, 0.1); border: 1px solid rgba(251, 191, 36, 0.2);">
                        <p style="font-size: 10px; color: #fbbf24; margin: 0 0 4px 0; text-transform: uppercase;">Pending</p>
                        <p style="font-size: 20px; font-weight: 700; color: #fbbf24; margin: 0;">{{ $this->previewPending }}</p>
                    </div>
                    <div style="text-align: center; padding: 12px; border-radius: 8px; background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.2);">
                        <p style="font-size: 10px; color: #22c55e; margin: 0 0 4px 0; text-transform: uppercase;">Selesai</p>
                        <p style="font-size: 20px; font-weight: 700; color: #22c55e; margin: 0;">{{ $this->previewSelesai }}</p>
                    </div>
                    <div style="text-align: center; padding: 12px; border-radius: 8px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2);">
                        <p style="font-size: 10px; color: #ef4444; margin: 0 0 4px 0; text-transform: uppercase;">Batal</p>
                        <p style="font-size: 20px; font-weight: 700; color: #ef4444; margin: 0;">{{ $this->previewBatal }}</p>
                    </div>
                    <div style="text-align: center; padding: 12px; border-radius: 8px; background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);">
                        <p style="font-size: 10px; color: #818cf8; margin: 0 0 4px 0; text-transform: uppercase;">Total</p>
                        <p style="font-size: 20px; font-weight: 700; color: #818cf8; margin: 0;">{{ $this->previewTotal }}</p>
                    </div>
                </div>
                {{-- Total Nominal full width --}}
                <div style="margin-top: 12px; text-align: center; padding: 16px; border-radius: 8px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3);">
                    <p style="font-size: 10px; color: #22c55e; margin: 0 0 4px 0; text-transform: uppercase;">Total Nominal (Selesai)</p>
                    <p style="font-size: 24px; font-weight: 700; color: #22c55e; margin: 0;">Rp {{ number_format($this->previewNominal, 0, ',', '.') }}</p>
                </div>
            </div>
        </div>
        @endif

        <div>
            <x-filament::button wire:click="downloadReport" icon="heroicon-o-document-arrow-down">
                Unduh Laporan
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
