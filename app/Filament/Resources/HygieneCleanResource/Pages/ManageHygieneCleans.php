<?php

namespace App\Filament\Resources\HygieneCleanResource\Pages;

use App\Filament\Resources\HygieneCleanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageHygieneCleans extends ManageRecords
{
    protected static string $resource = HygieneCleanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
