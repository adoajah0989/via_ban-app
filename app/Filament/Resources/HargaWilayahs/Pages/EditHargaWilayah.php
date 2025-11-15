<?php

namespace App\Filament\Resources\HargaWilayahs\Pages;

use App\Filament\Resources\HargaWilayahs\HargaWilayahResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHargaWilayah extends EditRecord
{
    protected static string $resource = HargaWilayahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
