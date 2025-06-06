<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryTransactionsResource\Pages;
use App\Filament\Resources\InventoryTransactionsResource\RelationManagers;
use App\Models\InventoryTransactions;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InventoryTransactionsResource extends Resource
{
    protected static ?string $model = InventoryTransactions::class;

    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 106;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=>Filament::getTenant()->id),
                Forms\Components\Select::make('transaction_type')
                    ->options([
                        'IN' => 'Incoming Stock',
                        'OUT' => 'Outgoing Stock',
                        'ADJUSTMENT' => 'Adjustment',
                        'RETURN' => 'Customer Return',
                    ])
                    ->required()
                    ->native(false),
                Forms\Components\TextInput::make('quantity_changed')
                    ->required()
                    ->integer()
                    ->numeric(),
                Forms\Components\Hidden::make('transaction_date')
                    ->default(now())
                    ->required(),
                Forms\Components\TextInput::make('source_destination')
                    ->maxLength(255),
                Forms\Components\TextInput::make('reference_number')
                    ->maxLength(100),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Forms\Components\RichEditor::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
            ])
            ->defaultSort('created_at', 'desc')
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
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth(MaxWidth::FitContent),
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth(MaxWidth::FitContent),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInventoryTransactions::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBetween('transaction_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
