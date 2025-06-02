<?php

namespace App\Filament\Resources\BirdReceivedResource\Pages;

use App\Filament\Resources\BirdReceivedResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageBirdReceiveds extends ManageRecords
{
    protected static string $resource = BirdReceivedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
