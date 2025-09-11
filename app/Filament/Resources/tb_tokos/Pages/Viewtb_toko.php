<?php

namespace App\Filament\Resources\tb_tokos\Pages;

use App\Filament\Resources\tb_tokos\tb_tokoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class Viewtb_toko extends ViewRecord
{
    protected static string $resource = tb_tokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
