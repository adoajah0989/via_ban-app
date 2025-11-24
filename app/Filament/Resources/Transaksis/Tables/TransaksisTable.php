<?php

namespace App\Filament\Resources\Transaksis\Tables;

use App\Models\tb_limbah as Limbah;
use App\Models\tb_transaksi as Transaksi;
use App\Services\HargaWilayahResolver;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransaksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_transaksi')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
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
                    ->label('edit')
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
                                    ->distinct(),

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
                        $idPusat = $record->toko->id_pusat ?? null;

                        // Bangun ulang mapping id_limbah => jumlah dari data modal
                        $quantities = [];
                        foreach ($data['details'] ?? [] as $detail) {
                            $nama = $detail['nama_limbah'] ?? null;
                            if (! $nama) {
                                continue;
                            }
                            $idLimbah = Limbah::where('nama_limbah', $nama)->value('id_limbah');
                            if (! $idLimbah) {
                                continue;
                            }
                            $jumlah = (int) ($detail['jumlah'] ?? 0);
                            if ($jumlah <= 0) {
                                continue;
                            }
                            $quantities[(int) $idLimbah] = $jumlah;
                        }

                        // Hitung ulang harga & subtotal per limbah berdasarkan wilayah & pusat
                        $prices = HargaWilayahResolver::getFor(array_keys($quantities), $kodeWilayah, $idPusat);

                        $rows = [];
                        $totalPickup = 0;
                        $totalSales = 0;

                        foreach ($quantities as $idLimbah => $jumlah) {
                            $harga = (int) ($prices[$idLimbah] ?? 0);
                            $subtotal = $jumlah * $harga;

                            $totalPickup += $jumlah;
                            $totalSales += $subtotal;

                            $rows[] = [
                                'id_limbah' => $idLimbah,
                                'jumlah' => $jumlah,
                                'harga_saat_transaksi' => $harga,
                                'subtotal' => $subtotal,
                            ];
                        }

                        // Sinkron ulang detail_transaksi dari hasil modal
                        $record->details()->delete();
                        if (! empty($rows)) {
                            $record->details()->createMany($rows);
                        }

                        // Update total di tb_transaksi
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
