<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button wire:click="downloadReport" icon="heroicon-o-document-arrow-down">
                Unduh Laporan
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
