<?php

namespace App\Filament\Resources\HargaWilayahs\Pages;

use App\Filament\Resources\HargaWilayahs\HargaWilayahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHargaWilayahs extends ListRecords
{
    protected static string $resource = HargaWilayahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Harga Baru'),
        ];
    }
}
