<?php

namespace App\Filament\Resources\Transaksis\Tables;

use App\Models\Post;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\SelectColumn;
use Filament\Actions\Action;
use App\Models\tb_transaksi as Transaksi;
use App\Models\tb_pengepul as Pengepul;
use App\Models\tb_limbah as Limbah;
use App\Services\PengepulReportService;

use function Laravel\Prompts\select;

class variable
{
    public $nama_limbah;

    public function __construct($value)
    {
        $this->nama_limbah = $value;
    }
}
class TransaksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->date('d M Y')->sortable(),
                TextColumn::make('toko.nama_toko')->label('Toko')->searchable()->sortable(),
                TextColumn::make('pengepul.nama')->label('Pengepul')->searchable(),
                TextColumn::make('sales')->label('Total')->money('IDR', true)->sortable(),
                SelectColumn::make('status')

                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'selesai' => 'Selesai',
                        'batal'   => 'Batal',
                    ])
                    ->selectablePlaceholder(false)
                    ->sortable(),

            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('edit')
                    ->fillForm(
                        fn(Transaksi $record) => [
                            'details' => $record->details()

                                ->with('limbah')
                                ->get()
                                ->map(fn($d) => [
                                    'id_detail' => $d->id_detail, // tambahkan ini
                                    'nama_limbah' => $d->limbah->nama_limbah ?? '-',
                                    'jumlah' => $d->jumlah,
                                    'harga_saat_transaksi' => 'Rp ' . $d->harga_saat_transaksi,
                                    'subtotal' => 'Rp ' . ((float) ($d->jumlah ?? 0) * (float) ($d->harga_saat_transaksi ?? 0)),
                                ])->toArray(),
                        ]
                    )
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->modalHeading('Detail Transaksi - Jumlah Limbah')
                    ->modalWidth('lg')
                    ->schema([
                        // Daftar detail limbah pada transaksi
                        Repeater::make('details')
                            ->schema([
                                TextInput::make('id')->hidden(),
                                Select::make('nama_limbah')
                                    ->label('Jenis Limbah')
                                    ->options(fn() => Limbah::query()->orderBy('nama_limbah')->pluck('nama_limbah', 'nama_limbah'))
                                    ->required()
                                    ->distinct()
                                    ,

                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->required(),
                                TextInput::make('harga_saat_transaksi')
                                    ->label('Harga aktual')
                                    ->disabled(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->disabled(),
                            ])
                            ->columns(4)

                            ->required(),
                    ])
                    ->action(function (array $data, Transaksi $record): void {
                        foreach ($data['details'] as $detail) {
                            if (!empty($detail['id_detail'])) {
                                $id_limbah = Limbah::where('nama_limbah', $detail['nama_limbah'])->value('id_limbah');
                                $record->details()
                                    ->where('id_detail', $detail['id_detail'])
                                    ->update([

                                        'id_limbah' => $id_limbah,
                                        'harga_saat_transaksi' => Limbah::where('id_limbah', $id_limbah)->value('harga'),
                                        'jumlah' => $detail['jumlah'],
                                    ]);
                            }
                        }
                        $record->load('details.limbah');
                        $totalPickup = (int) ($record->details->sum('jumlah'));
                        $totalSales = (int) ($record->details->sum(function ($d) {
                            return (int) ($d->jumlah ?? 0) * (int) ($d->limbah->harga ?? 0);
                        }));

                        // Optionally update id_limbah in the main record, but only if you want to set it to the last processed detail's id_limbah
                        $record->update([
                            'id_limbah' => $record->details->last()->id_limbah ?? null,
                            'total_pickup' => $totalPickup,
                            'sales' => $totalSales,
                        ]);
                    }),



            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
