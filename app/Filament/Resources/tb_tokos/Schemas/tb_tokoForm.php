<?php

namespace App\Filament\Resources\tb_tokos\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Livewire\Attributes\Reactive;
use App\Models\tb_wilayah;

class tb_tokoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_toko')
                    ->label('Nama Toko')
                    ->required()
                    ->maxLength(255),
                Select::make('kode_wilayah')
                    ->label('Kode Wilayah')
                    ->required()
                    ->options(
                        \App\Models\tb_wilayah::all()
                            ->mapWithKeys(function ($item) {
                                return [$item->kode_wilayah => $item->kode_wilayah . ' - ' . $item->nama_wilayah];
                            })
                            ->toArray()
                    )
                    ->reactive() // biar bisa trigger perubahan
                    ->afterStateUpdated(function ($state, callable $set) {
                        // reset kode_toko saat ganti wilayah
                        $set('kode_toko', $state);
                    })
                    ->searchable(),

                TextInput::make('kode_toko')
                    ->label('Kode Toko')
                    ->required()
                    ->minLength(6)
                    ->maxLength(6)
                    ->unique(ignoreRecord: true)
                    ->helperText('Format: 3 huruf diikuti 3 angka (contoh: ABC123)')
                    ->afterStateUpdated(function ($state, callable $set) {
                        $normalized = strtoupper((string) $state);
                        $set('kode_toko', $normalized);

                        if (!preg_match('/^[A-Za-z]{3}\d{3}$/', $normalized)) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Format Kode Toko Salah')
                                ->body('Kode toko harus 6 karakter: 3 huruf diikuti 3 angka (contoh: ABC123).')
                                ->send();
                        }
                    }),
                TextInput::make('alamat')
                    ->label('Alamat')
                    ->maxLength(255),
                TextInput::make('nomor_telepon')
                    ->label('Nomor telepon')
                    ->required()
                    ->maxLength(20),
            ]);
    }
}
