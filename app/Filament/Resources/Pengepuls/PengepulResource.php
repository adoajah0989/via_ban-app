<?php

namespace App\Filament\Resources\Pengepuls;

use App\Filament\Resources\Pengepuls\Pages\CreatePengepul;
use App\Filament\Resources\Pengepuls\Pages\EditPengepul;
use App\Filament\Resources\Pengepuls\Pages\ListPengepuls;
use App\Filament\Resources\Pengepuls\Pages\ViewPengepul;
use App\Filament\Resources\Pengepuls\Schemas\PengepulForm;
use App\Filament\Resources\Pengepuls\Schemas\PengepulInfolist;
use App\Filament\Resources\Pengepuls\Tables\PengepulsTable;
use App\Models\tb_pengepul;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;


class PengepulResource extends Resource
{
    protected static ?string $model = tb_pengepul::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::User;

    protected static ?string $recordTitleAttribute = 'pengepul';

    public static function getNavigationLabel(): string
    {
        return 'Daftar Pengepul';
    }
    public static function getModelLabel(): string
    {
        return 'pengepul'; // label singular
    }

    public static function form(Schema $schema): Schema
    {
        return PengepulForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PengepulInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengepulsTable::configure($table);
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
            'index' => ListPengepuls::route('/'),
            'create' => CreatePengepul::route('/create'),
            'view' => ViewPengepul::route('/{record}'),
            'edit' => EditPengepul::route('/{record}/edit'),
        ];
    }


}
