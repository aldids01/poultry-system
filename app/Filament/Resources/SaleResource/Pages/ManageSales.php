<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;

class ManageSales extends ManageRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent)
                ->successRedirectUrl(fn ($record) => SaleResource::getUrl('payments', ['record' => $record->id]))
                ->after(function (Sale $record) { // This is the key hook for creation
                    // Call the static helper method from SaleResource for consistency
                    SaleResource::processSaleInventoryInAction($record, 'OUT', 'Stock debited for Sale #');
                }),
        ];
    }
}
