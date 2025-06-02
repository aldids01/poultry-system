<?php

namespace App\Filament\Resources\InventoryTransactionsResource\Widgets;

use App\Models\InventoryTransactions;
use Filament\Forms\Components\DatePicker;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ProcessingLog extends BaseWidget
{
    protected static ?string $heading = '';
    public function table(Table $table): Table
    {
        return $table
            ->query(
               InventoryTransactions::query()->with('product')->whereHas('product', function (Builder $query) {
                   $query->where('product_type', 'raw_material');
               })
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('factory.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'IN' => 'success',
                        'OUT' => 'danger',
                        'ADJUSTMENT' => 'warning',
                        'RETURN' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('quantity_changed')
                    ->numeric()
                    ->icon(fn (int $state): string => $state > 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_destination')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Performed By')
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('transaction_type')
                    ->options([
                        'IN' => 'Incoming Stock',
                        'OUT' => 'Outgoing Stock',
                        'ADJUSTMENT' => 'Adjustment',
                        'RETURN' => 'Customer Return',
                    ]),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ]);
    }
}
