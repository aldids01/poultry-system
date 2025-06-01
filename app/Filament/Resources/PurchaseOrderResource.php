<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers;
use App\Models\InventoryTransactions;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationGroup = 'Purchase Order';
    protected static ?int $navigationSort = 104;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('po_number')
                    ->required()
                    ->readOnly()
                    ->default(fn () => 'PO-' . now()->format('Ymd') . '-' . str_pad(PurchaseOrder::whereDate('created_at', now()->toDateString())->count() + 1, 4, '0', STR_PAD_LEFT))
                    ->maxLength(255),
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'supplier_name', fn (Builder $query) => $query->where('factory_id', Filament::getTenant()->id))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->createOptionForm([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TextInput::make('supplier_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('contact_person')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(50),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                            ])->columns(2),
                        Forms\Components\RichEditor::make('address')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=>Filament::getTenant()->id)
                            ->required(),
                    ])->createOptionModalHeading('Create Supplier')
                    ->required(),
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=>Filament::getTenant()->id)
                    ->required(),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->required(),
                Forms\Components\DateTimePicker::make('order_date')
                    ->date()
                    ->default(now())
                    ->required(),
                Forms\Components\DateTimePicker::make('delivery_date')
                    ->date()
                    ->minDate(now())
                    ->required(),
                Forms\Components\Section::make('Order Items')
                    ->headerActions([
                        // Action to manually trigger total recalculation in case of issues
                        Forms\Components\Actions\Action::make('recalculate_total')
                            ->label('Recalculate Total')
                            ->icon('heroicon-m-arrow-path')
                            ->action(function (Forms\Get $get, Forms\Set $set) {
                                self::calculateTotalAmount($get, $set);
                                Notification::make()->title('Total recalculated.')->success()->send();
                            }),
                    ])
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('factory_id')
                                    ->default(fn()=>Filament::getTenant()->id)
                                    ->required(),
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->required()
                                    ->relationship('product', 'name', fn (Builder $query) => $query->where('factory_id', Filament::getTenant()->id))
                                    ->searchable()
                                    ->preload()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                    ->live() // Essential for live unit price update
                                    ->afterStateUpdated(function (?string $state, Forms\Set $set, Forms\Get $get) {
                                        $product = Product::find($state);
                                        if ($product) {
                                            $set('unit_price', $product->cost);
                                            // Recalculate subtotal immediately
                                            $quantity = (float) $get('quantity');
                                            $set('subtotal', round($product->cost * $quantity, 2));
                                        }
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true) // For live subtotal and total amount update
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $quantity = (float) $get('quantity');
                                        $unitPrice = (float) $get('unit_price');
                                        $set('subtotal', round($quantity * $unitPrice, 2));
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->required()
                                    ->readOnly()
                                    ->prefix('₦')
                                    ->live(onBlur: true) // For live subtotal and total amount update
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                        $quantity = (float) $get('quantity');
                                        $unitPrice = (float) $get('unit_price');
                                        $set('subtotal', round($quantity * $unitPrice, 2));
                                    }),
                                Forms\Components\Hidden::make('subtotal')
                                    ->disabled()
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('Add Item')
                            ->live() // Repeater needs to be live to trigger afterStateUpdated for total
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set) {
                                self::calculateTotalAmount($get, $set);
                            })
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(function (Forms\Get $get, Forms\Set $set) {
                                    self::calculateTotalAmount($get, $set);
                                }),
                            )
                            ->columnSpanFull(),
                    ]),


                Forms\Components\TextInput::make('total')
                    ->required()
                    ->label('Purchase Order Total')
                    ->inlineLabel()
                    ->columnSpanFull()
                    ->readOnly()
                    ->prefix('₦')
                    ->numeric()
                    ->default(0.00),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull(),
            ]);
    }
    protected static function calculateTotalAmount(Forms\Get $get, Forms\Set $set): void
    {
        $items = $get('items');
        $totalAmount = 0;

        if (is_array($items)) {
            foreach ($items as $item) {
                $subtotal = (float) ($item['subtotal'] ?? 0);
                $totalAmount += $subtotal;
            }
        }
        $set('total', round($totalAmount, 2));
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.supplier_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('factory.name')
                    ->numeric()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->sortable(),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->numeric()
                    ->placeholder('Pending')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->label('Order By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('order_date')
                    ->dateTime()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->dateTime()
                    ->label('Expected Delivery Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_date')
                    ->dateTime()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->summarize(Sum::make())
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Approved' => 'success',
                        'Pending' => 'danger',
                        'Cancelled' => 'warning',
                        'Received' => 'info',
                        'Posted' => 'primary',
                    }),
                Tables\Columns\TextColumn::make('payment')
                    ->badge()->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'Completed' => 'success',
                        'Pending' => 'danger',
                        'Cancelled' => 'warning',
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make(
                    [
                        Tables\Actions\Action::make('Payment')
                            ->icon('heroicon-s-credit-card')
                            ->visible(fn($record)=>$record->status == 'Approved')
                            ->url(fn ($record) => PurchaseOrderResource::getUrl('payments', ['record' => $record->id])),
                        Tables\Actions\Action::make('approve')
                            ->label('Approve PO')
                            ->icon('heroicon-o-check-circle')
                            ->modalIcon('heroicon-o-check-circle')
                            ->color('success')
                            ->requiresConfirmation()
                            ->visible(fn (PurchaseOrder $record): bool => $record->status === 'Pending')
                            ->action(function (PurchaseOrder $record) {
                                $record->status = 'Approved';
                                $record->approved_date = now();
                                $record->approved_by_id = auth()->id();
                                $record->save();

                                Notification::make()->title('Purchase Order Approved')->success()->send();
                            }),
                        Tables\Actions\Action::make('receive_inventory')
                            ->label('Post to Inventory')
                            ->icon('heroicon-o-cube')
                            ->modalIcon('heroicon-o-cube')
                            ->slideOver()
                            ->modalWidth(MaxWidth::FitContent)
                            ->color('primary')
                            ->visible(fn (PurchaseOrder $record): bool =>
                                $record->status === 'Approved' && $record->status === 'Received' ||
                                $record->payment === 'Completed' &&
                                !$record->isFullyReceived() // Only if not all items are received yet
                            )
                            ->form(fn (PurchaseOrder $record) => [
                                Forms\Components\Section::make('Receive Items')
                                    ->schema(
                                    // Dynamically generate inputs for each item not fully received
                                        $record->items->filter(fn ($item) => $item->quantity_received < $item->quantity)
                                            ->map(function ($item) {
                                                $remaining = $item->quantity - $item->quantity_received;
                                                return Forms\Components\TextInput::make('quantities.' . $item->id)
                                                    ->label($item->product->name . ' (Ordered: ' . $item->quantity . ', Received: ' . $item->quantity_received . ')')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue($remaining)
                                                    ->default($remaining)
                                                    ->required()
                                                    ->hidden(fn () => $remaining <= 0); // Hide if already fully received
                                            })->toArray()
                                    ),
                            ])
                            ->action(function (PurchaseOrder $record, array $data) {
                                DB::beginTransaction();
                                try {
                                    $quantitiesToReceive = $data['quantities'];
                                    $isPOFullyReceivedAfterThisReceipt = true; // Assume true, prove false

                                    foreach ($record->items as $item) {
                                        $qtyToReceive = $quantitiesToReceive[$item->id] ?? 0;

                                        if ($qtyToReceive > 0) {
                                            // 1. Update PurchaseOrderItem quantity_received
                                            $item->quantity_received += $qtyToReceive;
                                            $item->save();

                                            // 3. Create Inventory Transaction
                                            InventoryTransactions::create([
                                                'product_id' => $item->product_id,
                                                'factory_id' => $record->factory_id,
                                                'transaction_type' => 'IN', // 'IN' for inbound/receipt
                                                'quantity_changed' => $qtyToReceive,
                                                'transaction_date' => now(),
                                                'source_destination' => 'Purchase Order Receipt',
                                                'reference_number' => $record->po_number,
                                                'user_id' => auth()->id(),
                                                'notes' => 'Received from PO ' . $record->po_number . ' for product ' . $item->product->name,
                                            ]);
                                        }

                                        if ($item->quantity_received < $item->quantity) {
                                            $isPOFullyReceivedAfterThisReceipt = false; // If any item is not fully received, PO is not full
                                        }
                                    }

                                    // 4. Update Purchase Order status based on total received quantities
                                    if ($isPOFullyReceivedAfterThisReceipt) {
                                        $record->status = 'Posted';
                                    } elseif ($record->isPartiallyReceived()) { // Check if any item has been received at all
                                        $record->status = 'Received';
                                    }
                                    $record->save();

                                    DB::commit();

                                    Notification::make()
                                        ->title('Inventory Posted Successfully')
                                        ->body('Goods received and inventory updated for PO ' . $record->po_number)
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    Notification::make()
                                        ->title('Error Posting Inventory')
                                        ->body('An error occurred: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            }),
                        Tables\Actions\ViewAction::make()
                            ->slideOver()
                            ->modalWidth(MaxWidth::FitContent),
                        Tables\Actions\EditAction::make()
                            ->slideOver()
                            ->visible(fn($record)=>$record->status == 'Pending')
                            ->modalWidth(MaxWidth::FitContent),
                        Tables\Actions\DeleteAction::make()
                            ->visible(fn($record)=>$record->status == 'Pending'),
                        Tables\Actions\ForceDeleteAction::make()
                            ->visible(fn($record)=>$record->status == 'Pending'),
                        Tables\Actions\RestoreAction::make()
                            ->visible(fn($record)=>$record->status == 'Pending'),
                    ]
                ),
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
            'index' => Pages\ManagePurchaseOrders::route('/'),
            'payments' => Pages\ManagePayment::route('/{record}/payments'),
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
