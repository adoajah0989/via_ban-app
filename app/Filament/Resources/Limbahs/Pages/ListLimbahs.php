<?php

namespace App\Filament\Resources\Limbahs\Pages;

use App\Filament\Resources\Limbahs\LimbahResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLimbahs extends ListRecords
{
    protected static string $resource = LimbahResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    public function getTitle(): string
        {
            return 'List limbah';
        }
}
