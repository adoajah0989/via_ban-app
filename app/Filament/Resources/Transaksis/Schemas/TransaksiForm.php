<?php

namespace App\Filament\Resources\Transaksis\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Components\Section;
use App\Models\tb_limbah as Limbah;


class TransaksiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('tanggal')->required(),
                Select::make('id_toko')
                    ->relationship('toko', 'nama_toko')
                    ->required(),
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
                        $all = Limbah::orderBy('nama_limbah')->get(['id_limbah', 'nama_limbah']);
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
                                });
                        }
                        return $components;
                    })())
                    ->columns(3),
            ]);
    }
}
