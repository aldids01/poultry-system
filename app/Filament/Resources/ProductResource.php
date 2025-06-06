<?php

namespace App\Filament\Resources;

use App\Filament\Exports\ProductExporter;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\InventoryTransactions;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 105;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\Hidden::make('slug')
                    ->default(fn():string => Str::slug(Str::random(8))),
                Forms\Components\ToggleButtons::make('product_type')
                    ->inline()
                    ->live()
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_good' => 'Finished Goods',
                    ])->default('raw_material'),
                Forms\Components\TextInput::make('quantity_on_hand')
                    ->numeric()
                    ->live()
                    ->required(fn (Forms\Get $get): bool => $get('product_type') === 'finished_good')
                    ->label('Quantity produced')
                    ->default(0)
                    ->hidden(fn (Forms\Get $get): bool => $get('product_type') === 'raw_material'),
                Forms\Components\Repeater::make('materials')
                    ->relationship('rawMaterials')
                    ->label('Bill of Materials')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=>Filament::getTenant()->id)
                            ->required(),
                        Forms\Components\Select::make('raw_material_id')
                            ->required()
                            ->reactive()
                            ->relationship('rawMaterial', 'name', fn (Forms\Get $get, Builder $query) =>
                            $query->where('product_type', 'raw_material')
                                ->where('quantity_on_hand', '>', 0) // Only show raw materials with stock
                                ->where('factory_id', Filament::getTenant()?->id)
                            )
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {
                                $selectedProductId = $get('raw_material_id');
                                if ($selectedProductId) {
                                    $product = Product::find($selectedProductId); // Correctly fetch the product
                                    if ($product) {
                                        $set('unit_cost', $product->cost); // Set unit_cost from product's cost
                                    } else {
                                        $set('unit_cost', null);
                                    }
                                } else {
                                    $set('unit_cost', null);
                                }
                                self::updateMaterialQty($set, $get, $component); // Recalculate after unit_cost is set
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        Forms\Components\TextInput::make('quantity_needed')
                            ->label('Quantity')
                            ->numeric()
                            ->integer()
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {
                                self::updateMaterialQty($set, $get, $component); // Recalculate when quantity changes
                            })
                            ->required(),
                        Forms\Components\TextInput::make('unit_cost')
                            ->label('unit_cost')
                            ->numeric()
                            ->readOnly()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {

                                self::updateMaterialQty($set, $get, $component);
                            })
                            ->required(),
                        Forms\Components\Hidden::make('item_subtotal')
                            ->disabled()
                            ->default(0),
                    ])->columns(3)
                    ->live()
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {

                        self::updateMaterialQty($set, $get, null);
                    })
                    ->deleteAction(
                        fn (Forms\Components\Actions\Action $action) => $action->after(function (Forms\Get $get, Forms\Set $set) {
                            self::updateMaterialQty($set, $get, null);
                        }),
                    )
                    ->hidden(fn (Forms\Get $get): bool => $get('product_type') === 'raw_material'),
                Forms\Components\TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->live()
                    ->default(0)
                    ->maxValue(9999999.99)
                    ->readOnly()
                    ->prefix('NGN'),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->maxValue(9999999.99)
                    ->default(0.00)
                    ->prefix('NGN'),
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=>Filament::getTenant()->id)
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull()
                    ->maxLength(255),
            ]);
    }
    protected static function updateMaterialQty(Forms\Set $set, Forms\Get $get, ?Forms\Components\Component $component): void
    {
        // When called from item-level, $component is not null.
        // When called from repeater-level afterStateUpdated or deleteAction, $component is null.
        // We need to get the quantity_needed and unit_cost from the *current* item being updated
        // or from the collection if it's a repeater-level update.

        // If component is not null, we are updating a specific item within the repeater
        if ($component) {
            $quantity = (float) $get('quantity_needed');
            $unitCost = (float) $get('unit_cost');
            $itemSubtotal = $quantity * $unitCost;
            $set('item_subtotal', $itemSubtotal);
        }


        $allItemsData = $get('../../materials') ?? [];

        $grandTotal = collect($allItemsData)->sum(function ($item) {
            return (float) ($item['item_subtotal'] ?? 0);
        });

        $set('../../cost', $grandTotal);
    }
    // This helper method processes inventory transactions for finished good production
    public static function processFinishedGoodProduction(Product $product): void
    {
        DB::transaction(function () use ($product) {

            if ($product->rawMaterials->isEmpty()) {
                Notification::make()
                    ->title('Warning: Finished good created/updated but no Bill of Materials defined.')
                    ->body("Product '{$product->name}' (ID: {$product->id}) is a finished good but has no raw materials specified for production.")
                    ->warning()
                    ->send();
                return;
            }

            // Iterate through Bill of Materials items and create ASSEMBLY_CONSUMPTION transactions
            foreach ($product->rawMaterials as $bomItem) {
                // Ensure raw material product exists and has enough quantity
                $rawMaterial = $bomItem->rawMaterial;
                if (!$rawMaterial) {
                    Notification::make()
                        ->title('Inventory Error: Raw Material Missing')
                        ->body("Raw material for '{$product->name}' (ID: {$bomItem->raw_material_id}) not found. Inventory not fully processed.")
                        ->danger()
                        ->send();
                    continue;
                }

                $qtyToConsume = $bomItem->quantity_needed; // Total raw material needed for this production run

                // Create ASSEMBLY_CONSUMPTION transaction (negative quantity)
                InventoryTransactions::create([
                    'product_id' => $rawMaterial->id,
                    'factory_id' => $product->factory_id ?? Filament::getTenant()?->id,
                    'transaction_type' => 'ASSEMBLY_CONSUMPTION',
                    'quantity_changed' => -$qtyToConsume, // Negative for consumption
                    'transaction_date' => now(),
                    'source_destination' => "Production for Product #{$product->id} - {$product->name}",
                    'reference_number' => "PROD-{$product->id}",
                    'user_id' => Auth::id(),
                    'notes' => "Consumed {$qtyToConsume} {$rawMaterial->unit_of_measure} of {$rawMaterial->name} for initial production of {$product->quantity_on_hand} units of {$product->name}.",
                ]);
            }

            // Notification for successful raw material consumption
            Notification::make()
                ->title('Finished Good Production Processed')
                ->body("Raw materials consumed for initial production of {$product->name}.")
                ->success()
                ->send();

        });
    }
    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('cost')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_value')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('factory.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
               Tables\Actions\ActionGroup::make([
                   Tables\Actions\Action::make('Manage stock')
                        ->url(fn($record) => 'products/'.$record->id.'/receive')
                        ->icon('heroicon-m-arrows-up-down'),
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
                    Tables\Actions\ExportBulkAction::make()
                        ->slideOver()
                        ->label('Export selected products')
                        ->modalWidth(MaxWidth::FitContent)
                        ->exporter(ProductExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
            'receive' => Pages\ManageReceiveStock::route('/{record}/receive'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
