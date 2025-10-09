<?php

namespace App\Filament\Resources\Transaksis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;


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
            
            Repeater::make('details')
                ->relationship('details') // relasi ke detail_transaksi
                ->schema([
                    Select::make('id_limbah')
                        ->relationship('limbah', 'nama_limbah')
                        ->required(),
                    TextInput::make('jumlah')
                        ->numeric()
                        ->required(),
                ])
                ->columns(2)
                ->addActionLabel('Tambah Barang'),
            ]);
    }
}
