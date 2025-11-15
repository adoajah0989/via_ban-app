<?php

namespace App\Filament\Resources\HargaWilayahs;

use App\Filament\Resources\HargaWilayahs\Pages\CreateHargaWilayah;
use App\Filament\Resources\HargaWilayahs\Pages\EditHargaWilayah;
use App\Filament\Resources\HargaWilayahs\Pages\ListHargaWilayahs;
use App\Filament\Resources\HargaWilayahs\Schemas\HargaWilayahForm;
use App\Filament\Resources\HargaWilayahs\Tables\HargaWilayahsTable;
use App\Models\tb_harga_wilayah as HargaWilayah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HargaWilayahResource extends Resource
{
    protected static ?string $model = HargaWilayah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getNavigationGroup(): ?string
    {
        return 'Data Master';
    }

    public static function getNavigationLabel(): string
    {
        return 'Harga Wilayah';
    }

    public static function getModelLabel(): string
    {
        return 'harga wilayah';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Harga per wilayah';
    }

    public static function form(Schema $schema): Schema
    {
        return HargaWilayahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HargaWilayahsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHargaWilayahs::route('/'),
            'create' => CreateHargaWilayah::route('/create'),
            'edit' => EditHargaWilayah::route('/{record}/edit'),
        ];
    }
}
