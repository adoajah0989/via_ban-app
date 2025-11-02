<?php

namespace App\Filament\Resources\Transaksis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\SelectColumn;
use Filament\Actions\Action;
use App\Models\tb_transaksi as Transaksi;
use App\Models\tb_pengepul as Pengepul;
use Illuminate\Support\Carbon;

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
                Action::make('view')
                    ->fillForm(
                        fn(Transaksi $record) => [
                            'details' => $record->details()
                                ->with('limbah')
                                ->get()
                                ->map(fn($d) => [
                                    'nama_limbah' => $d->limbah->nama_limbah ?? '-',
                                    'jumlah' => $d->jumlah,
                                    'harga_saat_transaksi' => 'Rp ' . $d->harga_saat_transaksi,
                                    'subtotal' => 'Rp ' . (float) ($d->jumlah ?? 0) * (float) ($d->harga_saat_transaksi ?? 0),
                                ])->toArray(),
                        ]
                    )
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('Detail Transaksi - Jumlah Limbah')
                    ->modalWidth('lg')
                    ->schema([
                        // Daftar detail limbah pada transaksi
                        Repeater::make('details')
                            ->schema([
                                TextInput::make('nama_limbah')
                                    ->label('Jenis Limbah')
                                    ->disabled(),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->disabled(),
                                TextInput::make('harga_saat_transaksi')
                                    ->label('Harga aktual')
                                    ->disabled(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->disabled(),
                    ]),

                EditAction::make('edit'),
            ])
            ->toolbarActions([
                Action::make('buat_laporan')
                    ->label('Buat Laporan')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->modalHeading('Buat Laporan Bulanan Pengepul')
                    ->modalWidth('md')
                    ->form([
                        Select::make('pengepul_id')
                            ->label('Pengepul')
                            ->options(fn() => Pengepul::query()->orderBy('nama')->pluck('nama', 'id_pengepul'))
                            ->searchable()
                            ->required(),
                        DatePicker::make('bulan')
                            ->label('Bulan')
                            ->displayFormat('F Y')
                            ->native(false)
                            ->required(),
                        Select::make('format')
                            ->label('Format')
                            ->options([
                                'pdf' => 'PDF',
                                'html' => 'HTML',
                            ])
                            ->default('pdf')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $pengepul = Pengepul::findOrFail($data['pengepul_id']);
                        $date = Carbon::parse($data['bulan']);
                        $start = $date->copy()->startOfMonth();
                        $end = $date->copy()->endOfMonth();

                        $transaksis = Transaksi::query()
                            ->with(['toko', 'details.limbah'])
                            ->where('id_pengepul', $pengepul->id_pengepul)
                            ->whereBetween('tanggal', [$start->toDateString(), $end->toDateString()])
                            ->orderBy('tanggal')
                            ->get();

                        // Flatten detail rows and compute totals
                        $rows = [];
                        $grandTotal = 0.0;
                        $perLimbah = [];
                        foreach ($transaksis as $trx) {
                            foreach ($trx->details as $d) {
                                $limbahNama = $d->limbah->nama_limbah ?? '-';
                                $subtotal = (float) ($d->jumlah ?? 0) * (float) ($d->harga_saat_transaksi ?? 0);
                                $grandTotal += $subtotal;
                                $perLimbah[$limbahNama] = ($perLimbah[$limbahNama] ?? 0) + $subtotal;
                                $rows[] = [
                                    'tanggal' => Carbon::parse($trx->tanggal)->format('d M Y'),
                                    'toko' => $trx->toko->nama_toko ?? '-',
                                    'limbah' => $limbahNama,
                                    'jumlah' => $d->jumlah,
                                    'harga' => $d->harga_saat_transaksi,
                                    'subtotal' => $subtotal,
                                ];
                            }
                        }

                        $bulanLabel = $start->format('F Y');
                        $title = 'Laporan Pengepul ' . $pengepul->nama . ' - ' . $bulanLabel;

                        // Build simple HTML (A4)
                        $currency = fn($n) => 'Rp ' . number_format((float)$n, 0, ',', '.');
                        $summaryRows = '';
                        foreach ($perLimbah as $ln => $tot) {
                            $summaryRows .= '<tr><td style="padding:6px 8px;border:1px solid #ccc;">' . htmlspecialchars($ln) . '</td><td style="padding:6px 8px;border:1px solid #ccc;text-align:right;">' . $currency($tot) . '</td></tr>';
                        }
                        if ($summaryRows === '') {
                            $summaryRows = '<tr><td colspan="2" style="padding:6px 8px;border:1px solid #ccc;text-align:center;color:#666;">Tidak ada data</td></tr>';
                        }

                        $detailRows = '';
                        foreach ($rows as $r) {
                            $detailRows .= '<tr>'
                                . '<td style="padding:6px 8px;border:1px solid #ccc;">' . htmlspecialchars($r['tanggal']) . '</td>'
                                . '<td style="padding:6px 8px;border:1px solid #ccc;">' . htmlspecialchars($r['toko']) . '</td>'
                                . '<td style="padding:6px 8px;border:1px solid #ccc;">' . htmlspecialchars($r['limbah']) . '</td>'
                                . '<td style="padding:6px 8px;border:1px solid #ccc;text-align:right;">' . htmlspecialchars((string)$r['jumlah']) . '</td>'
                                . '<td style="padding:6px 8px;border:1px solid #ccc;text-align:right;">' . $currency($r['harga']) . '</td>'
                                . '<td style="padding:6px 8px;border:1px solid #ccc;text-align:right;">' . $currency($r['subtotal']) . '</td>'
                                . '</tr>';
                        }
                        if ($detailRows === '') {
                            $detailRows = '<tr><td colspan="6" style="padding:6px 8px;border:1px solid #ccc;text-align:center;color:#666;">Tidak ada data</td></tr>';
                        }

                        $html = '<!doctype html><html><head><meta charset="utf-8">'
                            . '<title>' . htmlspecialchars($title) . '</title>'
                            . '<style> @page { size: A4; margin: 18mm; } body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #111; } h1 { font-size: 18px; margin: 0 0 6px; } h2 { font-size: 14px; margin: 16px 0 8px; } table { border-collapse: collapse; width: 100%; } .muted { color:#555 } .right { text-align:right } .signature { width:100%; margin-top:40px; border-collapse:collapse; page-break-inside: avoid; }.signature td { width:50%; text-align:center; vertical-align:bottom; padding-top:60px; }.signature .who { font-weight: normal; margin-bottom:40px; }.signature .line { margin: 60px auto 0; border-top:1px solid #000; padding-top:4px; width:70%; }} </style>'
                            . '</head><body>'
                            . '<h1>' . htmlspecialchars($title) . '</h1>'
                            . '<div class="muted">Periode: ' . htmlspecialchars($start->format('d M Y')) . ' - ' . htmlspecialchars($end->format('d M Y')) . '</div>'
                            . '<div class="muted">Dicetak: ' . htmlspecialchars(Carbon::now()->format('d M Y H:i')) . '</div>'
                            . '<h2>Ringkasan per Limbah</h2>'
                            . '<table><thead><tr>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;text-align:left;">Limbah</th>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;text-align:right;">Total</th>'
                            . '</tr></thead><tbody>' . $summaryRows . '</tbody></table>'
                            . '<h2>Detail Pengangkutan</h2>'
                            . '<table><thead><tr>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;">Tanggal</th>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;">Toko</th>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;">Jenis Limbah</th>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;text-align:right;">Jumlah</th>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;text-align:right;">Harga</th>'
                            . '<th style="padding:6px 8px;border:1px solid #ccc;text-align:right;">Subtotal</th>'
                            . '</tr></thead><tbody>' . $detailRows . '</tbody>'
                            . '<tfoot><tr><td colspan="5" class="right" style="padding:6px 8px;border:1px solid #ccc; font-weight:bold;">Grand Total</td><td style="padding:6px 8px;border:1px solid #ccc; font-weight:bold; text-align:right;">' . $currency($grandTotal) . '</td></tr></tfoot>'
                            . '</table>'
                            . '<table class="signature"><tr>'
                            . '<td><div class="who">Pengepul</div><div class="line">Tanda Tangan</div></td>'
                            . '<td><div class="who">Mengetahui</div><div class="line">Tanda Tangan</div></td>'
                            . '</tr></table>'

                            . '</body></html>';

                        $safeName = 'laporan-pengepul-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($pengepul->nama)) . '-' . $start->format('Y-m');
                        if (($data['format'] ?? 'pdf') === 'pdf' && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)->setPaper('a4', 'portrait');
                            return response()->streamDownload(function () use ($pdf) {
                                echo $pdf->output();
                            }, $safeName . '.pdf', [
                                'Content-Type' => 'application/pdf',
                            ]);
                        }
                        return response()->streamDownload(function () use ($html) {
                            echo $html;
                        }, $safeName . '.html', [
                            'Content-Type' => 'text/html; charset=UTF-8',
                        ]);
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
