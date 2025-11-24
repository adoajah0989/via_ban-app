<?php

namespace App\Filament\Resources\PusatTokos\Tables;

use App\Models\tb_pusat_toko as PusatToko;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PusatTokosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_pusat')
                    ->label('Nama Pusat')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kode_pusat')
                    ->label('Kode')
                    ->sortable(),
                TextColumn::make('kontak')
                    ->label('Kontak')
                    ->wrap(),
            ])
            ->defaultSort('nama_pusat');
    }
}

