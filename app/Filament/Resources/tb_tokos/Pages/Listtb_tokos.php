<?php

namespace App\Filament\Resources\tb_tokos\Pages;

use App\Filament\Resources\tb_tokos\tb_tokoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class Listtb_tokos extends ListRecords
{
    protected static string $resource = tb_tokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Toko Baru')
            ,
        ];
    }
    
        public function getTitle(): string
        {
            return 'List Toko';
        }
}
