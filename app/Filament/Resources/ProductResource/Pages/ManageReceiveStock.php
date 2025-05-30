<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ManageReceiveStock extends ManageRelatedRecords
{
    protected static string $resource = ProductResource::class;

    protected static string $relationship = 'inventoryTransactions';
    public Product $product;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public function mount($record = null): void
    {
        parent::mount($record);

        if ($record && is_numeric($record)) {
            $this->product = Product::findOrFail($record);
        }
    }
    public function getTitle(): string|Htmlable
    {
        return 'Manage Stock for '.$this->product->name;
    }
    public static function getNavigationLabel(): string
    {
        return 'Inventory Transactions';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('transaction_date')
                    ->default(now()),
                Forms\Components\TextInput::make('quantity_changed')
                    ->label('Quantity')
                    ->required()
                    ->columnSpanFull()
                    ->maxLength(255),
                Forms\Components\TextInput::make('source_destination')
                    ->label('Supplier/Source')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\TextInput::make('reference_number')
                    ->label('PO Number')
                    ->columnSpanFull()
                    ->nullable(),
                Forms\Components\RichEditor::make('notes')
                    ->columnSpanFull()
                    ->nullable(),
                Forms\Components\Hidden::make('transaction_type')
                    ->default('IN'),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=>Filament::getTenant()->id),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle('Receive Stock for ')
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')->dateTime(),
                Tables\Columns\TextColumn::make('quantity_changed')
                    ->icon(fn (int $state): string => $state > 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('source_destination'),
                Tables\Columns\TextColumn::make('reference_number'),
                Tables\Columns\TextColumn::make('transaction_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'IN' => 'success',
                        'OUT' => 'danger',
                        'ADJUSTMENT' => 'warning',
                        'RETURN' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Performed By'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->modalWidth(MaxWidth::FitContent)
                    ->modalHeading('Receive Stock for '.$this->product->name)
                    ->label('Receive Stock'),
                Tables\Actions\Action::make('adjustStock')
                    ->slideOver()
                    ->modalWidth(MaxWidth::FitContent)
                    ->label('Adjust Stock')
                    ->color('warning')
                    ->icon('heroicon-o-adjustments-vertical')
                    ->form([
                        Forms\Components\Select::make('adjustment_type')
                            ->options([
                                'add' => 'Add Stock (Correction)',
                                'remove' => 'Remove Stock (Damage/Loss)',
                            ])
                            ->required()
                            ->reactive(), // Make it reactive to show different min values

                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->rules([
                                // Validation to prevent removing more than available stock
                                fn (Forms\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if ($get('adjustment_type') === 'remove' && $value > $this->product->quantity_on_hand) {
                                        $fail("You cannot remove more than {$this->product->quantity_on_hand} items in stock.");
                                    }
                                },
                            ]),
                        Forms\Components\RichEditor::make('notes')
                            ->label('Reason for Adjustment')
                            ->required(),
                    ])
                    ->action(function (array $data) { // Removed $record parameter here
                        \DB::transaction(function () use ($data) {
                            $quantity = ($data['adjustment_type'] === 'remove') ? -$data['quantity'] : $data['quantity'];

                            // Use $this->product instead of $record
                            $this->product->inventoryTransactions()->create([
                                'transaction_type' => 'ADJUSTMENT',
                                'quantity_changed' => $quantity,
                                'transaction_date' => now(),
                                'source_destination' => 'Inventory Adjustment',
                                'reference_number' => null,
                                'user_id' => auth()->id(),
                                'notes' => $data['notes'],
                                'factory_id' => Filament::getTenant()?->id, // Add factory_id here
                            ]);
                            // Quantity_on_hand will be updated by the observer
                        });

                        Notification::make()
                            ->title('Stock adjusted successfully!')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('Back')
                    ->color('danger')
                    ->url(fn () => ProductResource::getUrl('index', ['tenant' => Filament::getTenant()]))
                    ->icon('heroicon-o-arrow-left') // Optional: Add an icon for clarity
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
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
