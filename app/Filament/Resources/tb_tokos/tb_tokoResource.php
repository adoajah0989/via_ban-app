<?php

namespace App\Filament\Resources\tb_tokos;

use App\Filament\Resources\tb_tokos\Pages\Createtb_toko;
use App\Filament\Resources\tb_tokos\Pages\Edittb_toko;
use App\Filament\Resources\tb_tokos\Pages\Listtb_tokos;
use App\Filament\Resources\tb_tokos\Pages\Viewtb_toko;
use App\Filament\Resources\tb_tokos\Schemas\tb_tokoForm;
use App\Filament\Resources\tb_tokos\Schemas\tb_tokoInfolist;
use App\Filament\Resources\tb_tokos\Tables\tb_tokosTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Models\tb_toko;

class tb_tokoResource extends Resource
{
    protected static ?string $model = tb_toko::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static ?string $recordTitleAttribute = 'toko';
    public static function getNavigationGroup(): ?string
    {
        return 'Data Master';
    }
    public static function getNavigationLabel(): string
    {
        return 'Daftar Toko';
    }

    public static function getModelLabel(): string
    {
        return 'toko'; // label singular
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data toko'; // label plural
    }
    public static function form(Schema $schema): Schema
    {
        return tb_tokoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return tb_tokoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return tb_tokosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Listtb_tokos::route('/'),
            'create' => Createtb_toko::route('/create'),
            'view' => Viewtb_toko::route('/{record}'),
            'edit' => Edittb_toko::route('/{record}/edit'),
        ];
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['nama_toko', 'kode_toko'];
    }
}
