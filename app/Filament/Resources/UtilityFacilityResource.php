<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UtilityFacilityResource\Pages;
use App\Filament\Resources\UtilityFacilityResource\RelationManagers;
use App\Models\UtilityFacility;
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

class UtilityFacilityResource extends Resource
{
    protected static ?string $model = UtilityFacility::class;

    protected static ?string $navigationGroup = 'Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supervisor_id')
                    ->columnSpanFull()
                    ->relationship('supervisor', 'name'),
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
                Forms\Components\Hidden::make('factory_id')
                    ->default(fn()=> Filament::getTenant()->id)
                    ->required(),
                Forms\Components\Repeater::make('items')
                    ->columnSpanFull()
                    ->relationship('lineItems')
                    ->schema([
                        Forms\Components\Select::make('utility_item_id')
                            ->required()
                            ->relationship('item', 'name')
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                            ])->createOptionModalHeading('Create Utility and Facility Item'),
                        Forms\Components\ToggleButtons::make('status')
                            ->required()
                            ->inline()
                            ->grouped()
                            ->options([
                                'Okay' => 'Okay',
                                'Needs Attention' => 'Needs Attention',
                            ]),
                        Forms\Components\TextInput::make('remarks')
                            ->maxLength(255),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=> Filament::getTenant()->id)
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
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
            'index' => Pages\ManageUtilityFacilities::route('/'),
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
