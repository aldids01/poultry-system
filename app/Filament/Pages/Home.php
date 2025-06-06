<?php

namespace App\Filament\Pages;

use App\Filament\Resources\UserResource\Widgets\RecentActivity;
use Carbon\Carbon;
use Filament\Pages\Dashboard;

class Home extends Dashboard
{
    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }
    protected function getHeaderWidgets(): array
    {
        return [
          RecentActivity::class,
        ];
    }
}
