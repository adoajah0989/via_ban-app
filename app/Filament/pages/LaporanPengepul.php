<?php

namespace App\Filament\Pages;

use App\Models\tb_pengepul as Pengepul;
use App\Models\tb_laporan_pengepul;
use App\Services\PengepulReportService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class LaporanPengepul extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationLabel = 'Laporan Pengepul';

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    protected static ?string $title = 'Laporan Pengepul';

    // Mengganti view agar kita bisa mengatur tata letak form dan table
    protected string $view = 'filament.pages.laporan-pengepul';

    public ?array $data = [];

    public function mount(): void
    {
        // Default data: format PDF, bulan saat ini
        $this->data = [
            'format' => 'pdf',
            'bulan' => now()->startOfMonth()->toDateString(),
        ];

        $this->form->fill($this->data);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Opsi Parameter Laporan')
                    ->description('Pilih pengepul dan periode untuk menghasilkan laporan limbah bulanan.')
                    ->icon('heroicon-m-funnel')
                    ->schema([
                        Select::make('pengepul_id')
                            ->label('Pengepul')
                            ->options(fn () => Pengepul::query()->orderBy('nama')->pluck('nama', 'id_pengepul'))
                            ->searchable()
                            ->required()
                            ->columnSpan(1), // Menggunakan 1 kolom

                        DatePicker::make('bulan')
                            ->label('Bulan Laporan')
                            ->displayFormat('F Y')
                            ->native(false) // Menggunakan Filament DatePicker (lebih modern)
                            ->maxDate(now())
                            ->required()
                            ->columnSpan(1),

                        Select::make('format')
                            ->label('Format File')
                            ->options([
                                'pdf' => 'PDF (.pdf)',
                                'html' => 'HTML (.html)',
                            ])
                            ->default('pdf')
                            ->required()
                            ->columnSpan(1),
                    ])->columns(3), // Mengatur semua field dalam 3 kolom

            ])
            ->statePath('data');
    }

    public function downloadReport(): StreamedResponse
    {
        $data = $this->form->getState();

        // Tambahkan validasi jika diperlukan sebelum memanggil service
        
        return PengepulReportService::download($data);
    }

    // Implementasi HasTable untuk menampilkan Riwayat Laporan
    public function table(Table $table): Table
    {
        return $table
            ->query(tb_laporan_pengepul::query()->with('pengepul'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pengepul.nama')
                    ->label('Nama Pengepul')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->date('d M Y H:i')
                    ->sortable(),

                TextColumn::make('file_path')
                    ->label('Format')
                    ->formatStateUsing(fn ($state) => strtoupper(pathinfo($state, PATHINFO_EXTENSION)))
                    ->badge()
                    ->color('info'), // Memberikan warna badge biru
            ])
            ->heading('Riwayat 20 Laporan Terakhir') // Judul untuk tabel riwayat
            ->emptyStateHeading('Belum ada laporan yang dibuat.')
            ->paginated([20]); // Membatasi riwayat hanya 20 entri per halaman.
    }
}