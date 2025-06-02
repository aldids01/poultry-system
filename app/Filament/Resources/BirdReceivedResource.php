<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BirdReceivedResource\Pages;
use App\Filament\Resources\BirdReceivedResource\RelationManagers;
use App\Models\BirdReceived;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BirdReceivedResource extends Resource
{
    protected static ?string $model = BirdReceived::class;

    protected static ?string $navigationGroup = 'Reports';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TimePicker::make('time_of_arrival')
                    ->time(),
                Forms\Components\TextInput::make('batch_number')
                    ->maxLength(255),
                Forms\Components\Select::make('supervisor_id')
                    ->relationship('supervisor', 'name'),
                Forms\Components\TextInput::make('vehicle_number')
                    ->maxLength(255),
                Forms\Components\TextInput::make('birds_delivered')
                    ->numeric(),
                Forms\Components\TextInput::make('birds_dea')
                    ->label('Birds Dead on Arrival')
                    ->numeric(),
                Forms\Components\TextInput::make('birds_accepted')
                    ->numeric(),
                Forms\Components\Select::make('recovery_officer_id')
                    ->relationship('recoveryOfficer', 'name'),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('time_of_arrival')
                    ->time('H:m:ia'),
                Tables\Columns\TextColumn::make('batch_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supervisor.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('birds_delivered')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('birds_dea')
                    ->label('Birds Dead on Arrival')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('birds_accepted')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('recoveryOfficer.name')
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
            'index' => Pages\ManageBirdReceiveds::route('/'),
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
