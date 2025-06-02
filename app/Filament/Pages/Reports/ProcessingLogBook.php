<?php

namespace App\Filament\Pages\Reports;

use App\Filament\Resources\InventoryTransactionsResource\Widgets\ProcessingLog;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;

class ProcessingLogBook extends Dashboard
{
    use HasFiltersAction;
    protected static string $routePath = 'processing';
    protected static ?string $title = 'Processing Log Book';
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

    protected function getHeaderWidgets(): array
    {
        return [
          ProcessingLog::class,
        ];
    }
}
