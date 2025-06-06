<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProformaResource\Pages;
use App\Filament\Resources\ProformaResource\RelationManagers;
use App\Models\InventoryTransactions;
use App\Models\Product;
use App\Models\Proforma;
use Carbon\Carbon;
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
use Illuminate\Support\Facades\DB;

class ProformaResource extends Resource
{
    protected static ?string $model = Proforma::class;

    protected static ?string $navigationGroup = 'Point of Sale';
    protected static ?int $navigationSort = 102;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=>Filament::getTenant()->id)
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->columnSpanFull()
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->createOptionForm([
                        Forms\Components\Grid::make()
                            ->columns(3)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\RichEditor::make('address')
                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=>Filament::getTenant()->id)
                            ->required(),
                    ])->createOptionModalHeading('Create Customer')
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->required(),
                Forms\Components\Repeater::make('items')
                    ->relationship('proformaItems')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=>Filament::getTenant()->id)
                            ->required(),
                        Forms\Components\Select::make('product_id')
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->reactive()
                            ->required()
                            ->preload()
                            ->native(false)
                            ->searchable()
                            ->relationship('product', 'name', fn (Forms\Get $get, Builder $query) =>
                            $query->where('product_type', 'finished_good')
                                ->where('quantity_on_hand', '>', 0) // Only show raw materials with stock
                                ->where('factory_id', Filament::getTenant()?->id))
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {

                                $selectedProductId = $get('product_id');
                                if ($selectedProductId) {
                                    $product = Product::find($selectedProductId);
                                    if ($product) {
                                        $set('unit_price', $product->price);
                                    } else {
                                        $set('unit_price', null);
                                    }
                                } else {
                                    $set('unit_price', null);
                                }

                                self::updateItemTotalAndGrandTotal($set, $get, $component);
                            }),
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->integer()
                            ->default(1)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {

                                self::updateItemTotalAndGrandTotal($set, $get, $component);
                            }),
                        Forms\Components\TextInput::make('unit_price')
                            ->required()
                            ->reactive()
                            ->readOnly()
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, Forms\Components\Component $component) {

                                self::updateItemTotalAndGrandTotal($set, $get, $component);
                            })
                            ->numeric(),
                        Forms\Components\Hidden::make('item_subtotal')
                            ->disabled()
                            ->default(0),
                    ])->columns(3),
                Forms\Components\TextInput::make('total')
                    ->required()
                    ->columnSpanFull()
                    ->prefix('NGN')
                    ->label('Proforma Grand Total')
                    ->inlineLabel()
                    ->default(0)
                    ->numeric()
                    ->live()
                    ->readOnly(),
            ]);
    }
    protected static function updateItemTotalAndGrandTotal(Forms\Set $set, Forms\Get $get, Forms\Components\Component $component): void
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
                Tables\Columns\TextColumn::make('factory.name')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Sale by')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Pending' => 'danger',
                        'Cancel' => 'warning',
                        'Processing' => 'info',
                    }),
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
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('Payment')
                        ->icon('heroicon-s-credit-card')
                        ->url(fn ($record) => ProformaResource::getUrl('payments', ['record' => $record->id])),
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
            'index' => Pages\ManageProformas::route('/'),
            'payments' => Pages\ManagePayment::route('/{record}/payments'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
