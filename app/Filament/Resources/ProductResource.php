<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Point of Sale';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Hidden::make('slug')
                    ->default(fn():string => Str::slug(Str::random(8))),
                Forms\Components\TextInput::make('quantity_on_hand')
                    ->numeric()
                    ->default(0)
                    ->nullable(),
                Forms\Components\ToggleButtons::make('product_type')
                    ->inline()
                    ->live()
                    ->options([
                        'raw_material' => 'Raw Material',
                        'finished_good' => 'Finished Goods',
                    ])->default('raw_material'),
                Forms\Components\Repeater::make('materials')
                    ->relationship('rawMaterials')
                    ->label('Bill of Materials')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=>Filament::getTenant()->id)
                            ->required(),
                        Forms\Components\Select::make('raw_material_id ')
                            ->required()
                            ->relationship('rawMaterial', 'name', fn (Forms\Get $get, Builder $query) =>
                                $query->where('product_type', 'raw_material')->where('quantity_on_hand', '>', 0)->where('factory_id', Filament::getTenant()?->id)
                            )
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {

                                $selectedProductId = $get('raw_material_id');
                                if ($selectedProductId) {
                                    $product = Product::query()->where('product_type', 'raw_material')->where($selectedProductId);
                                    if ($product) {
                                        $set('unit_price', $product->price);
                                    } else {
                                        $set('unit_price', null);
                                    }
                                } else {
                                    $set('unit_price', null);
                                }

                                self::updateItemTotalAndGrandTotal($set, $get, $component);
                            })
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                        Forms\Components\TextInput::make('quantity_needed')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(0.0001)
                            ->required(),
                        Forms\Components\TextInput::make('cost')
                            ->label('Cost (Per Unit of Raw Material)')
                            ->required()
                    ])->columns(3)
                    ->hidden(fn (Forms\Get $get): bool => $get('product_type') === 'raw_material'),
                Forms\Components\TextInput::make('cost')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->maxValue(9999999.99)
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
    protected static function updateMaterialQty(Forms\Set $set, Forms\Get $get, Forms\Components\Component $component): void
    {
        $quantity = (float) $get('quantity');
        $unitPrice = (float) $get('unit_price');

        $itemSubtotal = $quantity * $unitPrice;
        $set('item_subtotal', $itemSubtotal);
        $allItemsData = $get('../../items') ?? [];
        $grandTotal = collect($allItemsData)->sum(function ($item) {
            return (float) ($item['item_subtotal'] ?? 0);
        });

        $set('../../total', $grandTotal);
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
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state))),
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
