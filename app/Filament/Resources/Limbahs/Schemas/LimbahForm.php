<?php

namespace App\Filament\Resources\Limbahs\Schemas;

use Dom\Text;
use App\Models\tb_pusat_toko;
use Filament\Schemas\Schema;
use Filament\Schemas\Contracts\Forms\Form as SchemaForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;


class LimbahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('id_pusat')
                    ->label('Pusat Toko')
                    ->required()
                    ->options(fn () => tb_pusat_toko::query()
                        ->orderBy('nama_pusat')
                        ->pluck('nama_pusat', 'id_pusat')
                        ->toArray())
                    ->searchable(),
               TextInput::make('nama_limbah')
                    ->label('Nama Limbah')
                    ->required()
                    ->maxLength(255),
               TextInput::make('harga')
                    ->label('Harga Default (fallback)')
                    ->helperText('Harga per wilayah dapat diatur lewat menu Harga Wilayah.')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
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
