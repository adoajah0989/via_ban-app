<?php

namespace App\Filament\Resources\Transaksis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\SelectColumn;
use Filament\Actions\Action;
use App\Models\tb_transaksi as Transaksi;


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
                Action::make('view')
                    ->fillForm(
                        fn (Transaksi $record) => [
                            'details' => $record->details()
                                ->with('limbah')
                                ->get()
                                ->map(fn ($d) => [
                                    'nama_limbah' => $d->limbah->nama_limbah ?? '-',
                                    'jumlah' => $d->jumlah,
                                    'harga_saat_transaksi' =>'Rp '. $d->harga_saat_transaksi,
                                    'subtotal' => 'Rp '.(float) ($d->jumlah ?? 0) * (float) ($d->harga_saat_transaksi ?? 0),
                                ])->toArray(),
                        ]
                    )
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('Detail Transaksi — Jumlah Limbah')
                    ->modalWidth('lg')
                    ->schema([
                        // Daftar detail limbah pada transaksi
                        Repeater::make('details')
                            ->schema([
                                TextInput::make('nama_limbah')
                                    ->label('Jenis Limbah')
                                    ->disabled(),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('harga_saat_transaksi')
                                    ->label('Harga aktual')
                                    ->disabled(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->disabled(),
                    ]),

                EditAction::make('edit'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

