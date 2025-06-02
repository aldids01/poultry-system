<?php

namespace App\Filament\Resources\UtilityItemResource\Pages;

use App\Filament\Resources\UtilityItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageUtilityItems extends ManageRecords
{
    protected static string $resource = UtilityItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
