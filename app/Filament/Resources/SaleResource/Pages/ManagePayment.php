<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Sale;
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
    protected static string $resource = SaleResource::class;

    protected static string $relationship = 'payments';
    public Sale $sale;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public function mount($record = null): void
    {
        parent::mount($record);

        if ($record && is_numeric($record)) {
            $this->sale = Sale::findOrFail($record);
        }
    }
    public function getTitle(): string|Htmlable
    {
        return 'Payments for '.$this->sale->customer->name;
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
                Forms\Components\Hidden::make('customer_id')
                    ->default(fn()=> $this->sale->customer->id),
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
//                Forms\Components\ToggleButtons::make('method')
//                    ->required()
//                    ->inline()
//                    ->columnSpanFull()
//                    ->grouped()
//                    ->options([
//                        'Cash' => 'Cash',
//                        'Online' => 'Online',
//                        'POS' => 'POS',
//                        'Bank' => 'Bank',
//                        'Cheque' => 'Cheque',
//                    ])->default('Cash'),
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
                    ->label('Receive Payment')
                    ->modalHeading(fn() => 'Received Payment for ' .$this->sale->customer->name)
                    ->modalDescription(fn() => "Amount due: NGN".Number::format($this->sale->total, 2))
                    ->hidden(fn()=>$this->sale->status === 'Completed')
                    ->modalWidth(MaxWidth::FitContent),
                Tables\Actions\Action::make('Back')
                    ->color('danger')
                    ->url(fn () => SaleResource::getUrl('index', ['tenant' => Filament::getTenant()]))
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
