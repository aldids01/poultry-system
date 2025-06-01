<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use App\Models\Supplier;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ManagePayment extends ManageRelatedRecords
{
    protected static string $resource = SupplierResource::class;

    protected static string $relationship = 'payments';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public Supplier $supplier;
    public function mount($record = null): void
    {
        parent::mount($record);

        if ($record && is_numeric($record)) {
            $this->supplier = Supplier::findOrFail($record);
        }
    }
    public function getTitle(): string|Htmlable
    {
        return $this->supplier->supplier_name.' Payments History';
    }
    public static function getNavigationLabel(): string
    {
        return 'Payments';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Paid By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseOrder.total')
                    ->numeric()
                    ->summarize(Sum::make()),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount paid')
                    ->numeric()
                    ->summarize(Sum::make()),
                Tables\Columns\TextColumn::make('method')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cash' => 'success',
                        'Online' => 'danger',
                        'POS' => 'warning',
                        'Bank' => 'info',
                        'USSD' => 'gray',
                        'Cheque' => 'primary',
                    })
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
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
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
