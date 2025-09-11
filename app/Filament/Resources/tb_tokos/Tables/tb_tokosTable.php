<?php

namespace App\Filament\Resources\tb_tokos\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use App\Models\tb_toko;


class tb_tokosTable
{
    public static function configure(Table $table): Table
    {
        // Hitung total jumlah data pada tabel
        
        $totalCount = $table->getQuery()->count();
        return $table
        
            ->columns([
                TextColumn::make('nama_toko')->label('Nama Toko')->searchable()->sortable(),
                TextColumn::make('kode_toko')->label('Kode Toko')->searchable()->sortable(),
                TextColumn::make('kode_wilayah')->label('Kode Wilayah')->searchable()->sortable(),
                TextColumn::make('alamat')->label('Alamat')->searchable()->sortable()->wrap(),
                TextColumn::make('nomor_telepon')->label('Nomor Telepon')->searchable()->sortable(),
            ])
            ->filters([
                
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
                
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    Action::make('edit')
                        ->action(fn(tb_toko $record) => $record->delete())
                        ->requiresConfirmation(),
                    DeleteBulkAction::make(),
                ]),
                Action::make('total_count')
                    ->label('Jumlah toko: ' . $totalCount)
                    ->disabled(),
                
            ])
            
            ;
    }
}
