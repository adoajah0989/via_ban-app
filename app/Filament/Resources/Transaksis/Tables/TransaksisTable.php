<?php

namespace App\Filament\Resources\Transaksis\Tables;

use App\Models\tb_limbah as Limbah;
use App\Models\tb_transaksi as Transaksi;
use App\Services\HargaWilayahResolver;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TransaksisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_transaksi')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')->date('d M Y')->sortable(),
                TextColumn::make('toko.nama_toko')->label('Toko')->searchable()->sortable(),
                TextColumn::make('pengepul.nama')->label('Pengepul')->searchable(),
                TextColumn::make('sales')->label('Total')->money('IDR', true)->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'selesai' => 'success',
                        'batal' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'pending' => 'heroicon-o-clock',
                        'selesai' => 'heroicon-o-check-circle',
                        'batal' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'selesai' => 'Selesai',
                        'batal' => 'Batal',
                        default => $state,
                    })
                    ->sortable(),

            ])
            ->defaultSort('tanggal', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                
                \Filament\Actions\ActionGroup::make([
                    // Tombol Lihat Detail
                    Action::make('lihat_detail')
                        ->label('Lihat Detail')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->modalHeading(fn(Transaksi $record) => "Detail Transaksi: {$record->kode_transaksi}")
                        ->modalWidth('2xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->modalContent(function (Transaksi $record) {
                            $record->loadMissing(['toko', 'pengepul', 'details.limbah']);
                            return view('filament.transaksi-detail', ['record' => $record]);
                        }),

                    // Tombol Edit
                    Action::make('edit')
                        ->fillForm(
                            fn(Transaksi $record) => [
                                'details' => $record->details()
                                    ->with('limbah')
                                    ->get()
                                    ->map(fn($d) => [
                                        'id_detail' => $d->id_detail,
                                        'nama_limbah' => $d->limbah->nama_limbah ?? '-',
                                        'jumlah' => $d->jumlah,
                                        'harga_saat_transaksi' => 'Rp ' . $d->harga_saat_transaksi,
                                        'subtotal' => 'Rp ' . ((float) ($d->jumlah ?? 0) * (float) ($d->harga_saat_transaksi ?? 0)),
                                    ])->toArray(),
                            ]
                        )
                        ->label('Edit Transaksi')
                        ->icon('heroicon-o-pencil')
                        ->color('primary')
                        ->modalHeading('Detail Transaksi - Jumlah Limbah')
                        ->modalWidth('lg')
                        ->schema([
                            Repeater::make('details')
                                ->schema([
                                    TextInput::make('id_detail')->hidden(),
                                    Select::make('nama_limbah')
                                        ->label('Limbah')
                                        ->options(fn() => Limbah::query()->orderBy('nama_limbah')->pluck('nama_limbah', 'nama_limbah'))
                                        ->required()
                                        ->distinct(),

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
                            $record->loadMissing('toko');
                            $kodeWilayah = $record->toko->kode_wilayah ?? null;
                            $idPusat = $record->toko->id_pusat ?? null;

                            $quantities = [];
                            foreach ($data['details'] ?? [] as $detail) {
                                $nama = $detail['nama_limbah'] ?? null;
                                if (! $nama) {
                                    continue;
                                }
                                $idLimbah = Limbah::where('nama_limbah', $nama)->value('id_limbah');
                                if (! $idLimbah) {
                                    continue;
                                }
                                $jumlah = (int) ($detail['jumlah'] ?? 0);
                                if ($jumlah <= 0) {
                                    continue;
                                }
                                $quantities[(int) $idLimbah] = $jumlah;
                            }

                            $prices = HargaWilayahResolver::getFor(array_keys($quantities), $kodeWilayah, $idPusat);

                            $rows = [];
                            $totalPickup = 0;
                            $totalSales = 0;

                            foreach ($quantities as $idLimbah => $jumlah) {
                                $harga = (int) ($prices[$idLimbah] ?? 0);
                                $subtotal = $jumlah * $harga;

                                $totalPickup += $jumlah;
                                $totalSales += $subtotal;

                                $rows[] = [
                                    'id_limbah' => $idLimbah,
                                    'jumlah' => $jumlah,
                                    'harga_saat_transaksi' => $harga,
                                    'subtotal' => $subtotal,
                                ];
                            }

                            $record->details()->delete();
                            if (! empty($rows)) {
                                $record->details()->createMany($rows);
                            }

                            $record->update([
                                'total_pickup' => $totalPickup,
                                'sales' => $totalSales,
                            ]);
                        }),

                    // Tombol Validasi - hanya muncul untuk transaksi pending
                    Action::make('validasi')
                        ->label('Validasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Validasi')
                        ->modalDescription(fn(Transaksi $record) => "Apakah Anda yakin ingin memvalidasi transaksi {$record->kode_transaksi}? Status akan diubah menjadi 'selesai' dan notifikasi akan dikirim ke pengepul.")
                        ->modalSubmitActionLabel('Ya, Validasi')
                        ->visible(fn(Transaksi $record) => $record->status === 'pending')
                        ->action(function (Transaksi $record): void {
                            $record->update(['status' => 'selesai']);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Transaksi Tervalidasi')
                                ->body("Transaksi {$record->kode_transaksi} berhasil divalidasi. Notifikasi telah dikirim ke pengepul.")
                                ->success()
                                ->send();
                        }),

                    // Tombol Batalkan - hanya muncul untuk transaksi pending
                    Action::make('batalkan')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Pembatalan')
                        ->modalDescription(fn(Transaksi $record) => "Apakah Anda yakin ingin membatalkan transaksi {$record->kode_transaksi}?")
                        ->modalSubmitActionLabel('Ya, Batalkan')
                        ->visible(fn(Transaksi $record) => $record->status === 'pending')
                        ->action(function (Transaksi $record): void {
                            $record->update(['status' => 'batal']);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Transaksi Dibatalkan')
                                ->body("Transaksi {$record->kode_transaksi} telah dibatalkan.")
                                ->warning()
                                ->send();
                        }),
                ])
                ->label('Aksi')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),


            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('validasi_massal')
                        ->label('Validasi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Validasi Massal')
                        ->modalDescription('Apakah Anda yakin ingin memvalidasi semua transaksi yang dipilih? Status akan diubah menjadi "selesai" dan notifikasi akan dikirim ke pengepul.')
                        ->modalSubmitActionLabel('Ya, Validasi Semua')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $validated = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'selesai']);
                                    $validated++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Validasi Massal Berhasil')
                                ->body("{$validated} transaksi berhasil divalidasi. Notifikasi telah dikirim ke pengepul terkait.")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('batalkan_massal')
                        ->label('Batalkan Terpilih')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Konfirmasi Pembatalan Massal')
                        ->modalDescription('Apakah Anda yakin ingin membatalkan semua transaksi yang dipilih?')
                        ->modalSubmitActionLabel('Ya, Batalkan Semua')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $cancelled = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pending') {
                                    $record->update(['status' => 'batal']);
                                    $cancelled++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Pembatalan Massal Berhasil')
                                ->body("{$cancelled} transaksi berhasil dibatalkan.")
                                ->warning()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
