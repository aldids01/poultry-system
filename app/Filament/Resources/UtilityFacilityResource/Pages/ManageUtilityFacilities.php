<?php

namespace App\Filament\Resources\UtilityFacilityResource\Pages;

use App\Filament\Resources\UtilityFacilityResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageUtilityFacilities extends ManageRecords
{
    protected static string $resource = UtilityFacilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
