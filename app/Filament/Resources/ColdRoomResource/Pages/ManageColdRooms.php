<?php

namespace App\Filament\Resources\ColdRoomResource\Pages;

use App\Filament\Resources\ColdRoomResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageColdRooms extends ManageRecords
{
    protected static string $resource = ColdRoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
