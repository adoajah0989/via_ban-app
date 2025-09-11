<?php

namespace App\Filament\Resources\Limbahs;

use App\Filament\Resources\Limbahs\Pages\CreateLimbah;
use App\Filament\Resources\Limbahs\Pages\EditLimbah;
use App\Filament\Resources\Limbahs\Pages\ListLimbahs;
use App\Filament\Resources\Limbahs\Schemas\LimbahForm;
use App\Filament\Resources\Limbahs\Tables\LimbahsTable;
use App\Models\tb_limbah as Limbah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LimbahResource extends Resource
{
    protected static ?string $model = Limbah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'list limbah';

    public static function form(Schema $schema): Schema
    {
        return LimbahForm::configure($schema);
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Data Utama';
    }
    public static function table(Table $table): Table
    {
        return LimbahsTable::configure($table);
    }

    public static function getNavigationLabel(): string
    {
        return 'daftar limbah';
    }
    public static function getModelLabel(): string
    {
        return 'Limbah'; // label singular
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Limbah'; // label plural
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
            'index' => ListLimbahs::route('/'),
            'create' => CreateLimbah::route('/create'),
            'edit' => EditLimbah::route('/{record}/edit'),
        ];
    }
}
