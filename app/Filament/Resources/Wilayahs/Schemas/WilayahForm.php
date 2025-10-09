<?php

namespace App\Filament\Resources\Wilayahs\Schemas;

// removed unused import
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

// removed unused import

class WilayahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode_wilayah')
                    ->label('Kode Wilayah')
                    ->required()
                    ->maxLength(10),
                TextInput::make('nama_wilayah')
                    ->label('Nama Wilayah')
                    ->required()
                    ->maxLength(25),
                
            ]);
    }
}
