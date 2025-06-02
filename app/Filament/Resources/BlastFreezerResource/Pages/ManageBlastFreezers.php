<?php

namespace App\Filament\Resources\BlastFreezerResource\Pages;

use App\Filament\Resources\BlastFreezerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageBlastFreezers extends ManageRecords
{
    protected static string $resource = BlastFreezerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
