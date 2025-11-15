<?php

namespace App\Filament\Resources\HargaWilayahs\Schemas;

use App\Models\tb_limbah;
use App\Models\tb_wilayah;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class HargaWilayahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('kode_wilayah')
                ->label('Wilayah')
                ->options(fn () => tb_wilayah::query()
                        ->orderBy('nama_wilayah')
                        ->pluck('nama_wilayah', 'kode_wilayah')
                        ->toArray())
                ->required()
                ->searchable(),

            Select::make('id_limbah')
                ->label('Jenis Limbah')
                ->options(fn () => tb_limbah::query()
                        ->orderBy('nama_limbah')
                        ->pluck('nama_limbah', 'id_limbah')
                        ->toArray())
                ->required()
                ->searchable()
                ->rules([
                    fn (callable $get, ?Model $record) => Rule::unique('tb_harga_wilayah', 'id_limbah')
                        ->where(fn ($query) => $query->where('kode_wilayah', $get('kode_wilayah')))
                        ->ignore($record?->id),
                ]),

            TextInput::make('harga')
                ->label('Harga (IDR)')
                ->numeric()
                ->required()
                ->minValue(0),
        ])->columns(2);
    }
}
