<?php

namespace App\Filament\Resources\PusatTokos;

use App\Filament\Resources\PusatTokos\Pages\CreatePusatToko;
use App\Filament\Resources\PusatTokos\Pages\EditPusatToko;
use App\Filament\Resources\PusatTokos\Pages\ListPusatTokos;
use App\Filament\Resources\PusatTokos\Schemas\PusatTokoForm;
use App\Filament\Resources\PusatTokos\Tables\PusatTokosTable;
use App\Models\tb_pusat_toko as PusatToko;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PusatTokoResource extends Resource
{
    protected static ?string $model = PusatToko::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingOffice2;

    protected static ?string $recordTitleAttribute = 'nama_pusat';

    public static function getNavigationGroup(): ?string
    {
        return 'Data Master';
    }

    public static function getNavigationLabel(): string
    {
        return 'Pusat Toko';
    }

    public static function getModelLabel(): string
    {
        return 'Pusat Toko';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Pusat Toko';
    }

    public static function form(Schema $schema): Schema
    {
        return PusatTokoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PusatTokosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPusatTokos::route('/'),
            'create' => CreatePusatToko::route('/create'),
            'edit' => EditPusatToko::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_pusat', 'kode_pusat'];
    }
}

