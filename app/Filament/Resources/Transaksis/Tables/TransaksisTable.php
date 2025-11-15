<?php

namespace App\Filament\Resources\Transaksis\Tables;

use App\Models\tb_limbah as Limbah;
use App\Models\tb_transaksi as Transaksi;
use App\Services\HargaWilayahResolver;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransaksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->date('d M Y')->sortable(),
                TextColumn::make('toko.nama_toko')->label('Toko')->searchable()->sortable(),
                TextColumn::make('pengepul.nama')->label('Pengepul')->searchable(),
                TextColumn::make('sales')->label('Total')->money('IDR', true)->sortable(),
                SelectColumn::make('status')

                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'selesai' => 'Selesai',
                        'batal'   => 'Batal',
                    ])
                    ->selectablePlaceholder(false)
                    ->sortable(),

            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->fillForm(
                        fn(Transaksi $record) => [
                            'details' => $record->details()

                                ->with('limbah')
                                ->get()
                                ->map(fn($d) => [
                                    'id_detail' => $d->id_detail,
                                    'nama_limbah' => $d->limbah->nama_limbah ?? '-',
                                    'jumlah' => $d->jumlah,
                                    'harga_saat_transaksi' => 'Rp ' . $d->harga_saat_transaksi,
                                    'subtotal' => 'Rp ' . ((float) ($d->jumlah ?? 0) * (float) ($d->harga_saat_transaksi ?? 0)),
                                ])->toArray(),
                        ]
                    )
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->modalHeading('Detail Transaksi - Jumlah Limbah')
                    ->modalWidth('lg')
                    ->schema([
                        // Daftar detail limbah pada transaksi
                        Repeater::make('details')
                            ->schema([
                                TextInput::make('id_detail')->hidden(),
                                Select::make('nama_limbah')
                                    ->label('Jenis Limbah')
                                    ->options(fn() => Limbah::query()->orderBy('nama_limbah')->pluck('nama_limbah', 'nama_limbah'))
                                    ->required()
                                    ->distinct()
                                    ,

                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('harga_saat_transaksi')
                                    ->label('Harga aktual')
                                    ->disabled(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->disabled(),
                            ])
                            ->columns(4)

                            ->required(),
                    ])
                    ->action(function (array $data, Transaksi $record): void {
                        $record->loadMissing('toko');
                        $kodeWilayah = $record->toko->kode_wilayah ?? null;

                        $details = collect($data['details'] ?? [])
                            ->filter(fn ($detail) => ! empty($detail['id_detail']))
                            ->map(function ($detail) {
                                $idLimbah = Limbah::where('nama_limbah', $detail['nama_limbah'])->value('id_limbah');

                                return [
                                    'row_id' => $detail['id_detail'],
                                    'id_limbah' => (int) $idLimbah,
                                    'jumlah' => (int) ($detail['jumlah'] ?? 0),
                                ];
                            })
                            ->filter(fn ($detail) => $detail['id_limbah'] > 0);

                        $priceMap = HargaWilayahResolver::getFor($details->pluck('id_limbah')->all(), $kodeWilayah);

                        foreach ($details as $detail) {
                            $record->details()
                                ->where('id_detail', $detail['row_id'])
                                ->update([
                                    'id_limbah' => $detail['id_limbah'],
                                    'harga_saat_transaksi' => (int) ($priceMap[$detail['id_limbah']] ?? 0),
                                    'jumlah' => $detail['jumlah'],
                                ]);
                        }

                        $record->load('details');

                        $totalPickup = (int) $record->details->sum('jumlah');
                        $totalSales = (int) $record->details->sum(function ($d) {
                            return (int) ($d->jumlah ?? 0) * (int) ($d->harga_saat_transaksi ?? 0);
                        });

                        $record->update([
                            'total_pickup' => $totalPickup,
                            'sales' => $totalSales,
                        ]);
                    }),



            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
