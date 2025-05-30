<?php

namespace App\Filament\Resources\InventoryTransactionsResource\Pages;

use App\Filament\Resources\InventoryTransactionsResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageInventoryTransactions extends ManageRecords
{
    protected static string $resource = InventoryTransactionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
