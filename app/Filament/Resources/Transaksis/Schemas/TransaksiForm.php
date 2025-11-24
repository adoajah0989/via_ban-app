<?php

namespace App\Filament\Resources\Transaksis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;

use App\Models\tb_limbah as Limbah;
use App\Models\tb_toko;
use Filament\Schemas\Components\Utilities\Get as UtilitiesGet;

class TransaksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')->required(),
                Select::make('id_toko')
                    ->relationship('toko', 'nama_toko')
                    ->required()
                    ->reactive(),
                Select::make('id_pengepul')
                    ->relationship('pengepul', 'nama')
                    ->searchable()
                    ->preload()
                    ->label('Pengepul (opsional)')
                    ->nullable(),
                Section::make('Detail Limbah')
                    ->description('Isi jumlah untuk tiap limbah. Biarkan 0 jika tidak diambil.')
                    ->schema((function () {
                        $components = [];
                        $all = Limbah::orderBy('nama_limbah')->get(['id_limbah', 'nama_limbah', 'id_pusat']);
                        foreach ($all as $row) {
                            $id = (string) $row->id_limbah;
                            $label = (string) $row->nama_limbah;
                            $components[] = TextInput::make("limbah_qty.$id")
                                ->label($label)
                                ->numeric()
                                ->minValue(0)
                                ->default(0)
                                ->afterStateHydrated(function ($state, callable $set, $record) use ($id) {
                                    if ($record) {
                                        $record->loadMissing('details');
                                        $detail = $record->details->firstWhere('id_limbah', (int) $id);
                                        if ($detail) {
                                            $set("limbah_qty.$id", (int) ($detail->jumlah ?? 0));
                                        }
                                    }
                                })
                                ->hidden(function (UtilitiesGet $get) use ($row): bool {
                                    $tokoId = (int) $get('id_toko');

                                    // Jika toko belum dipilih → sembunyikan
                                    if ($tokoId <= 0) {
                                        return true;
                                    }

                                    // Ambil id_pusat dari tb_toko
                                    $toko = tb_toko::select('id_pusat')->find($tokoId);

                                    // Jika toko tidak ditemukan → sembunyikan
                                    if (! $toko || ! $toko->id_pusat) {
                                        return true;
                                    }

                                    // Cocokkan pusat form row dengan pusat toko
                                    $rowPusat   = (int) ($row->id_pusat ?? 0);
                                    $tokoPusat  = (int) $toko->id_pusat;

                                    // Jika pusat berbeda → sembunyikan
                                    return $rowPusat !== $tokoPusat;
                                });
                        }
                        return $components;
                    })())
                    ->columns(3),
            ]);
    }
}
