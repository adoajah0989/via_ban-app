<?php

namespace App\Filament\Pages;

use App\Models\tb_pengepul as Pengepul;
use App\Models\tb_laporan_pengepul;
use App\Models\tb_transaksi;
use App\Services\PengepulReportService;
use BackedEnum;
use Carbon\Carbon;
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

    protected string $view = 'filament.pages.laporan-pengepul';

    public ?array $data = [];
    
    // Preview summary properties
    public int $previewPending = 0;
    public int $previewSelesai = 0;
    public int $previewBatal = 0;
    public int $previewTotal = 0;
    public float $previewNominal = 0;
    public bool $showPreview = false;

    public function mount(): void
    {
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
                            ->live()
                            ->afterStateUpdated(fn () => $this->updatePreview())
                            ->columnSpan(1),

                        DatePicker::make('bulan')
                            ->label('Bulan Laporan')
                            ->displayFormat('F Y')
                            ->native(false)
                            ->maxDate(now())
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $this->updatePreview())
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
                    ])->columns(3),

            ])
            ->statePath('data');
    }

    public function updatePreview(): void
    {
        $pengepulId = $this->data['pengepul_id'] ?? null;
        $bulan = $this->data['bulan'] ?? null;

        if (!$pengepulId || !$bulan) {
            $this->showPreview = false;
            return;
        }

        $date = Carbon::parse($bulan);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $query = tb_transaksi::where('id_pengepul', $pengepulId)
            ->whereBetween('tanggal', [$startOfMonth, $endOfMonth]);

        $this->previewPending = (clone $query)->where('status', 'pending')->count();
        $this->previewSelesai = (clone $query)->where('status', 'selesai')->count();
        $this->previewBatal = (clone $query)->where('status', 'batal')->count();
        $this->previewTotal = (clone $query)->count();
        $this->previewNominal = (clone $query)->where('status', 'selesai')->sum('sales');
        
        $this->showPreview = true;
    }

    public function downloadReport(): StreamedResponse
    {
        $data = $this->form->getState();
        
        return PengepulReportService::download($data);
    }

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
                    ->color('info'),
            ])
            ->heading('Riwayat 20 Laporan Terakhir')
            ->emptyStateHeading('Belum ada laporan yang dibuat.')
            ->paginated([20]);
    }
}