<?php

namespace App\Filament\Resources\ProformaResource\Pages;

use App\Filament\Resources\ProformaResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageProformas extends ManageRecords
{
    protected static string $resource = ProformaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->successRedirectUrl(fn ($record) => ProformaResource::getUrl('payments', ['record' => $record->id]))
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
