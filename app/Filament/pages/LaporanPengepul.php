<?php

namespace App\Filament\Pages;

use App\Models\tb_pengepul as Pengepul;
use App\Services\PengepulReportService;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPengepul extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static ?string $navigationLabel = 'Laporan Pengepul';

    public static function getNavigationGroup(): ?string
    {
        return 'Laporan';
    }

    protected static ?string $title = 'Laporan Pengepul';

    protected string $view = 'filament.pages.laporan-pengepul';

    public ?array $data = [];

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
                Select::make('pengepul_id')
                    ->label('Pengepul')
                    ->options(fn () => Pengepul::query()->orderBy('nama')->pluck('nama', 'id_pengepul'))
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
            ->statePath('data');
    }

    public function downloadReport(): StreamedResponse
    {
        $data = $this->form->getState();

        return PengepulReportService::download($data);
    }
}
