<?php

namespace App\Filament\Resources\Limbahs\Schemas;

use Dom\Text;
use Filament\Schemas\Schema;
use Filament\Schemas\Contracts\Forms\Form as SchemaForm;
use Filament\Forms\Components\TextInput;


class LimbahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
               TextInput::make('nama_limbah')
                    ->label('Nama Limbah')
                    ->required()
                    ->maxLength(255),
               TextInput::make('harga')
                    ->label('Harga')
                    ->required()
                    ->maxLength(50),
                TextInput::make('kode_limbah')
                    ->label('Kode Limbah')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true)
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('kode_limbah', strtoupper($state));
                    }),
            ]);
    }
}
