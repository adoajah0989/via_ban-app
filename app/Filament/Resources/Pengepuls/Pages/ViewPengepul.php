<?php

namespace App\Filament\Resources\Pengepuls\Pages;

use App\Filament\Resources\Pengepuls\PengepulResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPengepul extends ViewRecord
{
    protected static string $resource = PengepulResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
