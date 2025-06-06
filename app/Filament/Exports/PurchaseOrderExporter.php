<?php

namespace App\Filament\Exports;

use App\Models\PurchaseOrder;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PurchaseOrderExporter extends Exporter
{
    protected static ?string $model = PurchaseOrder::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('po_number'),
            ExportColumn::make('supplier.id'),
            ExportColumn::make('factory.name'),
            ExportColumn::make('approvedBy.name'),
            ExportColumn::make('user.name'),
            ExportColumn::make('order_date'),
            ExportColumn::make('delivery_date'),
            ExportColumn::make('approved_date'),
            ExportColumn::make('remarks'),
            ExportColumn::make('total'),
            ExportColumn::make('status'),
            ExportColumn::make('payment'),
            ExportColumn::make('deleted_at'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your purchase order export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
