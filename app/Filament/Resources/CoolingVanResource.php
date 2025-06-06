<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoolingVanResource\Pages;
use App\Filament\Resources\CoolingVanResource\RelationManagers;
use App\Models\CoolingVan;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Number;

class CoolingVanResource extends Resource
{
    protected static ?string $model = CoolingVan::class;

    protected static ?string $navigationGroup = 'Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('driver_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('supervisor_id')
                    ->relationship('supervisor', 'name'),
                Forms\Components\Repeater::make('items')
                    ->relationship('vanItems')
                    ->columnSpanFull()
                    ->schema([
                        Forms\Components\TimePicker::make('departure_time')
                            ->required()
                            ->time(),
                        Forms\Components\TextInput::make('amount_products_carried')
                            ->required()
                            ->reactive()
                            ->live(onBlur: true)
                            ->default(0)
                            ->numeric(),
                        Forms\Components\TextInput::make('delivery_location')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TimePicker::make('return_time')
                            ->required()
                            ->time(),
                        Forms\Components\TextInput::make('fuel_level')
                            ->required()
                            ->numeric()
                            ->reactive()
                            ->live(onBlur: true)
                            ->default(0),
                        Forms\Components\TextInput::make('remarks')
                            ->nullable()
                            ->maxLength(255),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=> Filament::getTenant()->id)
                            ->required(),
                    ])->columns(6),
                Forms\Components\Placeholder::make('grand_total')
                    ->label('Total')
                    ->inlineLabel()
                    ->content(function (Forms\Get $get, Forms\Set $set){
                        $total = 0;
                        $repeaterItems = $get('items');
                        if (is_array($repeaterItems)) {
                            foreach ($repeaterItems as $repeaterRow) {
                                $total += (int) ($repeaterRow['amount_products_carried'] ?? 0);
                            }
                        }
                        $set('total', $total);
                        return Number::format($total, 2);
                    })->extraAttributes(['class' => 'font-bold text-right']),
                Forms\Components\Placeholder::make('grand_total_fuel')
                    ->label('Fuel Level Total')
                    ->inlineLabel()
                    ->content(function (Forms\Get $get, Forms\Set $set){
                        $total_fuel = 0;
                        $repeaterItems = $get('items');
                        if (is_array($repeaterItems)) {
                            foreach ($repeaterItems as $repeaterRow) {
                                $total_fuel += (int) ($repeaterRow['fuel_level'] ?? 0);
                            }
                        }
                        $set('fuel_total', $total_fuel);
                        return Number::format($total_fuel, 2);
                    })->extraAttributes(['class' => 'font-bold text-right']),
                hidden::make('total')
                    ->default(0),
                Forms\Components\Hidden::make('fuel_total')
                    ->default(0),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=> Filament::getTenant()->id)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('driver_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fuel_total')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('factory.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
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
            'index' => Pages\ManageCoolingVans::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereBetween('created_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
    }
}
