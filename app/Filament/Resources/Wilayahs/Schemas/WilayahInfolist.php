<?php

namespace App\Filament\Resources\Wilayahs\Schemas;

use Filament\Schemas\Schema;

use function Laravel\Prompts\text;

class WilayahInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            ]);
    }
}
