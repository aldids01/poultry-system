<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class ManagePayment extends ManageRelatedRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static string $relationship = 'payments';
    public PurchaseOrder $sale;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public function mount($record = null): void
    {
        parent::mount($record);

        if ($record && is_numeric($record)) {
            $this->sale = PurchaseOrder::findOrFail($record);
        }
    }
    public function getTitle(): string|Htmlable
    {
        return 'Payments for '.$this->sale->supplier->supplier_name;
    }

    public static function getNavigationLabel(): string
    {
        return 'Payments';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=>Filament::getTenant()->id)
                    ->required(),
                Forms\Components\Hidden::make('supplier_id')
                    ->default(fn()=> $this->sale->supplier->id),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->required(),
                Forms\Components\ToggleButtons::make('status')
                    ->required()
                    ->inline()
                    ->columnSpanFull()
                    ->grouped()
                    ->options([
                        'Processing' => 'Processing',
                        'Completed' => 'Completed',
                        'Cancel' => 'Cancel',
                    ])->default('Completed'),
                Forms\Components\ToggleButtons::make('method')
                    ->required()
                    ->inline()
                    ->columnSpanFull()
                    ->grouped()
                    ->options([
                        'Cash' => 'Cash',
                        'Online' => 'Online',
                        'POS' => 'POS',
                        'Bank' => 'Bank',
                        'USSD' => 'USSD',
                        'Cheque' => 'Cheque',
                    ])->default('Cash'),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->prefix('NGN')
                    ->default(fn()=> $this->sale->total)
                    ->columnSpanFull()
                    ->numeric(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Received By')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->label('Make Payment')
                    ->modalHeading(fn() => 'Make Payment for ' .$this->sale->supplier->supplier_name)
                    ->modalDescription(fn() => "Amount due: NGN".Number::format($this->sale->total, 2))
                    ->hidden(fn()=>$this->sale->status === 'Completed')
                    ->modalWidth(MaxWidth::FitContent),
                Tables\Actions\Action::make('Back')
                    ->color('danger')
                    ->url(fn () => PurchaseOrderResource::getUrl('index', ['tenant' => Filament::getTenant()]))
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
