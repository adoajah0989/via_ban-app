<?php

namespace App\Filament\Resources\Transaksis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Hidden;
use App\Models\tb_limbah as Limbah;


class TransaksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')->required(),
                Select::make('id_toko')
                    ->relationship('toko', 'nama_toko')
                    ->required(),
                Select::make('id_pengepul')
                    ->relationship('pengepul', 'nama')
                    ->searchable()
                    ->preload()
                    ->label('Pengepul (opsional)')
                    ->nullable(),

                Repeater::make('details')
                    ->relationship('details') // relasi ke detail_transaksi
                    ->schema([
                        Select::make('id_limbah')
                            ->relationship('limbah', 'nama_limbah')
                            ->required()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $harga = optional(Limbah::find($state))->harga ?? 0;
                                $set('harga_saat_transaksi', $harga);
                            }),
                        TextInput::make('jumlah')
                            ->numeric()  
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Hidden::make('harga_saat_transaksi'),
                    ])
                    ->columns(2)
                    ->addActionLabel('Tambah Barang'),

            ]);
    }
}
