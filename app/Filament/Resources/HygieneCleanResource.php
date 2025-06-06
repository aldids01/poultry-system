<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HygieneCleanResource\Pages;
use App\Filament\Resources\HygieneCleanResource\RelationManagers;
use App\Models\HygieneClean;
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

class HygieneCleanResource extends Resource
{
    protected static ?string $model = HygieneClean::class;

    protected static ?string $navigationGroup = 'Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supervisor_id')
                    ->relationship('supervisor', 'name')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Repeater::make('items')
                    ->columnSpanFull()
                    ->label('Items')
                    ->relationship('hygiene')
                    ->schema([
                        Forms\Components\Select::make('area_id')
                            ->required()
                            ->relationship('area', 'name')
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->columnSpanFull()
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('description')
                                    ->columnSpanFull(),
                            ])
                            ->createOptionModalHeading('Create area'),
                        Forms\Components\ToggleButtons::make('status')
                            ->required()
                            ->inline()
                            ->grouped()
                            ->options([
                                'Clean' => 'Clean',
                                'Dirty' => 'Dirty',
                            ]),
                        Forms\Components\TextInput::make('remarks')
                            ->maxLength(255),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Hidden::make('factory_id')
                            ->default(fn()=> Filament::getTenant()->id)
                            ->required(),
                    ])->columns(3),
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
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->sortable()
                    ->label('Recorded by')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ManageHygieneCleans::route('/'),
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
