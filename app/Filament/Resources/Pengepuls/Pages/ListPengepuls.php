<?php

namespace App\Filament\Resources\Pengepuls\Pages;

use App\Filament\Resources\Pengepuls\PengepulResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengepuls extends ListRecords
{
    protected static string $resource = PengepulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
