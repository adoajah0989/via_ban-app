<?php

namespace App\Filament\Resources\Pengepuls\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Support\View\Components\ModalComponent;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Models\tb_pengepul as Pengepuls;
use Filament\Forms\Components\TextInput;


class PengepulsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable()->sortable(),
                TextColumn::make('nomor_telepon')->label('Nomor Telepon')->searchable()->sortable()->wrap(),
                TextColumn::make('nomor_kendaraan')->label('Nomor Kendaraan')->searchable()->sortable()->wrap(),
                TextColumn::make('telegramAccount.telegram_user_id')
                    ->label('Telegram ID')
                    ->toggleable()
                    ->searchable(),
            ])
            ->filters([])
            ->recordActions([
                Action::make('-')
                    ->fillForm(
                        fn(Pengepuls $record) => $record->toArray()
                    )
                    ->schema([
                        TextInput::make('nama')->label('Nama')->disabled(),
                        TextInput::make('nomor_telepon')->label('Nomor Telepon')->disabled(),
                        TextInput::make('nomor_kendaraan')->label('Nomor Kendaraan')->disabled(),
                    ])
                    ->modalHeading('Detail Pengepul')
                    ->modalWidth('md')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('Detail Pengepul')
                    ->modalWidth('md'),


                EditAction::make('edit')
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('edit')
                        ->action(fn(Pengepuls $record) => $record->delete())
                        ->requiresConfirmation(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
