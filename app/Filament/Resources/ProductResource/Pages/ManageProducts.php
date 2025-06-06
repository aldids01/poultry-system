<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Exports\ProductExporter;
use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->slideOver()
                ->modalWidth(MaxWidth::FitContent)
                ->after(function (Product $record) { // This is the key hook for creation
                    // Only process inventory transactions if it's a finished good
                    if ($record->product_type === 'finished_good') {
                        ProductResource::processFinishedGoodProduction($record);
                    }
                }),
            Actions\ExportAction::make()
                ->color('info')
                ->slideOver()
                ->label('Export all products')
                ->modalWidth(MaxWidth::FitContent)
                ->exporter(ProductExporter::class),
        ];
    }
}
