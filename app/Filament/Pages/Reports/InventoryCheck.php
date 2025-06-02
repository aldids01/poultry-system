<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Resources\ProductResource\Widgets\InventoryCheckOverviewWidge;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class InventoryCheck extends Dashboard
{
    use HasFiltersAction;
    protected static string $routePath = 'inventory-check';
    protected static ?string $title = 'Material Inventory Checklist';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationIcon = '';
    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->form([
                    DatePicker::make('startDate'),
                    DatePicker::make('endDate'),
                    // ...
                ]),
        ];
    }
    public static function canView(): bool
    {
        return true;
    }
    protected function getHeaderWidgets(): array
    {
        return [
            InventoryCheckOverviewWidge::class,
        ];
    }
}
