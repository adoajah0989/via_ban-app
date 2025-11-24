<?php

namespace App\Filament\Resources\PusatTokos\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PusatTokoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama_pusat')
                ->label('Nama Pusat')
                ->required()
                ->maxLength(255),

            TextInput::make('kode_pusat')
                ->label('Kode Pusat')
                ->required()
                ->maxLength(10)
                ->unique(ignoreRecord: true)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    $set('kode_pusat', strtoupper((string) $state));
                }),

            TextInput::make('kontak')
                ->label('Kontak')
                ->maxLength(255)
                ->helperText('Opsional, misalnya nomor telepon atau email.'),
        ])->columns(2);
    }
}

