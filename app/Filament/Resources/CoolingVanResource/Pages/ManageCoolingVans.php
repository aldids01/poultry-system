<?php

namespace App\Filament\Resources\CoolingVanResource\Pages;

use App\Filament\Resources\CoolingVanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageCoolingVans extends ManageRecords
{
    protected static string $resource = CoolingVanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
