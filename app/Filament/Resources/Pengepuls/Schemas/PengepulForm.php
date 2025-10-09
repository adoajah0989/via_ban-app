<?php

namespace App\Filament\Resources\Pengepuls\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PengepulForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nomor_telepon')
                    ->label('Nomor Telepon')
                    ->maxLength(15)
                    ->unique(ignoreRecord: true),
                TextInput::make('nomor_kendaraan')
                    ->label('Nomor Kendaraan')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
            ]);
    }
}
