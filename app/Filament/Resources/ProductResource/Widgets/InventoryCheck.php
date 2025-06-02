<?php

namespace App\Filament\Resources\ProductResource\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class InventoryCheck extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(
               Product::query()->where('product_type', 'raw_material')
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('product_type')
                    ->searchable()
                    ->badge()
                    ->color(fn (Product $record): string => match ($record->product_type) {
                        'raw_material' => 'primary',
                        'finished_good' => 'success',
                    })
                    ->formatStateUsing(function (string $state, Product $record): string { // Add $record to the closure
                        if ($state ==='finished_good') {
                            return $record->on_sale ? 'Finished Goods' : 'On Sale'; // Show 'On Sale' if true, else 'Finished Good'
                        }
                        return ucwords(str_replace('_', ' ', $state)); // For other types, format normally
                    }),
                Tables\Columns\TextColumn::make('quantity_on_hand')
                    ->numeric()
                    ->label('Current Stock')
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
