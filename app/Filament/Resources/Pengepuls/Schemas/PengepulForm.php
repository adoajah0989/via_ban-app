<?php

namespace App\Filament\Resources\Pengepuls\Schemas;

use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PengepulForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nomor_telepon')
                    ->label('Nomor Telepon')
                    ->maxLength(15)
                    ->unique(ignoreRecord: true),
                TextInput::make('nomor_kendaraan')
                    ->label('Nomor Kendaraan')
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                Fieldset::make('Akun Telegram')
                    ->schema([
                        TextInput::make('telegram_user_id')
                            ->label('Telegram User ID')
                            ->numeric()
                            ->helperText('Diisi admin, sesuai user.id Telegram pengepul (1:1).')
                            ->maxLength(20),
                        TextInput::make('telegram_username')
                            ->label('Username Telegram')
                            ->helperText('Opsional, tanpa @, hanya untuk referensi.')
                            ->maxLength(64),
                    ]),
            ]);
    }
}
