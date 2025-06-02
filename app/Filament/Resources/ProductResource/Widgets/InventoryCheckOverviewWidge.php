<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;


class InventoryCheckOverviewWidge extends BaseWidget
{
    protected static ?string $heading = '';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()->where('product_type', 'raw_material')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Items')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->numeric()
                    ->label('Quantity Available')
                    ->sortable()
                    ->color(fn (Product $record): string => match (true) {
                        $record->quantity_on_hand <= ($record->reorderSetting?->reorder_point ?? 0) && $record->quantity_on_hand > 0 => 'warning',
                        $record->quantity_on_hand === 0 => 'danger',
                        default => 'success',
                    })
                    ->summarize(Sum::make())
                    ->sortable(),
            ]);
    }
}
