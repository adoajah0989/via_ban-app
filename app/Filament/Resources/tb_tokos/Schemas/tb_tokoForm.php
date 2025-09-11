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
                                return [$item->kode_wilayah => $item->kode_wilayah . ' - ' . $item->wilayah];
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
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Ambil kode_wilayah (3 huruf) + input user (angka)
                        $kodeWilayah = $get('kode_wilayah');
                        if ($kodeWilayah) {
                            // contoh: user ketik 003 -> hasil TGR003
                            $set('kode_toko', $kodeWilayah . str_pad(preg_replace('/\D/', '', $state), 3, '0', STR_PAD_LEFT));
                        }
                    })
                    ->maxLength(8),
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
