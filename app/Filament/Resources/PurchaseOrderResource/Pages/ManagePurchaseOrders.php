<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManagePurchaseOrders extends ManageRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent),
        ];
    }
}
