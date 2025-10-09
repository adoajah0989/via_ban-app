<?php

namespace App\Filament\Resources\Transaksis;

use App\Filament\Resources\Transaksis\Pages\CreateTransaksi;
use App\Filament\Resources\Transaksis\Pages\EditTransaksi;
use App\Filament\Resources\Transaksis\Pages\ListTransaksis;
use App\Filament\Resources\Transaksis\Pages\ViewTransaksi;
use App\Filament\Resources\Transaksis\Schemas\TransaksiForm;
use App\Filament\Resources\Transaksis\Schemas\TransaksiInfolist;
use App\Filament\Resources\Transaksis\Tables\TransaksisTable;
use App\Models\tb_transaksi as Transaksi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'transaksi';

    public static function form(Schema $schema): Schema
    {
        return TransaksiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransaksiInfolist::configure($schema);
    }
    public static function getNavigationLabel(): string
    {
        return 'Transaksi';
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Transaksi';
    }
    public static function getModelLabel(): string
    {
        return 'Transaksi'; // label singular
    }

    public static function getPluralModelLabel(): string
    {
        return 'Data Transaksi'; // label plural
    }

    public static function table(Table $table): Table
    {
        return TransaksisTable::configure($table);
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
            'index' => ListTransaksis::route('/'),
            'create' => CreateTransaksi::route('/create'),
            'view' => ViewTransaksi::route('/{record}'),
            'edit' => EditTransaksi::route('/{record}/edit'),
        ];
    }
}
