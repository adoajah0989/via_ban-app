<?php

namespace App\Filament\Resources\tb_tokos\Pages;

use App\Filament\Resources\tb_tokos\tb_tokoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class Edittb_toko extends EditRecord
{
    protected static string $resource = tb_tokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
