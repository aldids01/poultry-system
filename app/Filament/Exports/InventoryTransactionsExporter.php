<?php

namespace App\Filament\Exports;

use App\Models\InventoryTransactions;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class InventoryTransactionsExporter extends Exporter
{
    protected static ?string $model = InventoryTransactions::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('transaction_id'),
            ExportColumn::make('product.name'),
            ExportColumn::make('factory.name'),
            ExportColumn::make('transaction_type'),
            ExportColumn::make('quantity_changed'),
            ExportColumn::make('transaction_date'),
            ExportColumn::make('source_destination'),
            ExportColumn::make('reference_number'),
            ExportColumn::make('user.name'),
            ExportColumn::make('notes'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your inventory transactions export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
