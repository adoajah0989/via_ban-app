<?php

namespace App\Filament\Resources\PusatTokos\Pages;

use App\Filament\Resources\PusatTokos\PusatTokoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPusatToko extends EditRecord
{
    protected static string $resource = PusatTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Hapus'),
        ];
    }
}

