<?php

namespace App\Filament\Resources\PusatTokos\Pages;

use App\Filament\Resources\PusatTokos\PusatTokoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPusatTokos extends ListRecords
{
    protected static string $resource = PusatTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Pusat Toko Baru'),
        ];
    }
}

