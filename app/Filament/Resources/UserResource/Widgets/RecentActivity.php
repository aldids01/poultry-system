<?php

namespace App\Filament\Resources\UserResource\Widgets;

use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\ActivitylogServiceProvider;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class RecentActivity extends BaseWidget
{
    public function table(Table $table): Table
    {
        return $table
            ->query(ActivityModel::query()->where('causer_id', auth()->id())->whereDate('created_at', Carbon::today()))
            ->columns([
                TextColumn::make('log_name')
                    ->badge()
                    ->colors(static::getLogNameColors())
                    ->label(__('filament-logger::filament-logger.resource.label.type'))
                    ->formatStateUsing(fn ($state) => ucwords($state))
                    ->sortable(),

                TextColumn::make('event')
                    ->label(__('filament-logger::filament-logger.resource.label.event'))
                    ->sortable(),

                TextColumn::make('description')
                    ->label(__('filament-logger::filament-logger.resource.label.description'))
                    ->wrap(),

                TextColumn::make('subject_type')
                    ->label(__('filament-logger::filament-logger.resource.label.subject'))
                    ->formatStateUsing(function ($state, Model $record) {
                        /** @var Activity&ActivityModel $record */
                        if (!$state) {
                            return '-';
                        }
                        return Str::of($state)->afterLast('\\')->headline().' # '.$record->subject_id;
                    }),

                TextColumn::make('created_at')
                    ->label(__('filament-logger::filament-logger.resource.label.logged_at'))
                    ->dateTime(config('filament-logger.datetime_format', 'd/m/Y H:i:s'), config('app.timezone'))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([]);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivities::route('/'),
            'view' => Pages\ViewActivity::route('/{record}'),
        ];
    }

    public static function getModel(): string
    {
        return ActivitylogServiceProvider::determineActivityModel();
    }

    protected static function getSubjectTypeList(): array
    {
        if (config('filament-logger.resources.enabled', true)) {
            $subjects = [];
            $exceptResources = [...config('filament-logger.resources.exclude'), config('filament-logger.activity_resource')];
            $removedExcludedResources = collect(Filament::getResources())->filter(function ($resource) use ($exceptResources) {
                return ! in_array($resource, $exceptResources);
            });
            foreach ($removedExcludedResources as $resource) {
                $model = $resource::getModel();
                $subjects[$model] = Str::of(class_basename($model))->headline();
            }
            return $subjects;
        }
        return [];
    }

    protected static function getLogNameList(): array
    {
        $customs = [];

        foreach (config('filament-logger.custom') ?? [] as $custom) {
            $customs[$custom['log_name']] = $custom['log_name'];
        }

        return array_merge(
            config('filament-logger.resources.enabled') ? [
                config('filament-logger.resources.log_name') => config('filament-logger.resources.log_name'),
            ] : [],
            config('filament-logger.models.enabled') ? [
                config('filament-logger.models.log_name') => config('filament-logger.models.log_name'),
            ] : [],
            config('filament-logger.access.enabled')
                ? [config('filament-logger.access.log_name') => config('filament-logger.access.log_name')]
                : [],
            config('filament-logger.notifications.enabled') ? [
                config('filament-logger.notifications.log_name') => config('filament-logger.notifications.log_name'),
            ] : [],
            $customs,
        );
    }

    protected static function getLogNameColors(): array
    {
        $customs = [];

        foreach (config('filament-logger.custom') ?? [] as $custom) {
            if (filled($custom['color'] ?? null)) {
                $customs[$custom['color']] = $custom['log_name'];
            }
        }

        return array_merge(
            (config('filament-logger.resources.enabled') && config('filament-logger.resources.color')) ? [
                config('filament-logger.resources.color') => config('filament-logger.resources.log_name'),
            ] : [],
            (config('filament-logger.models.enabled') && config('filament-logger.models.color')) ? [
                config('filament-logger.models.color') => config('filament-logger.models.log_name'),
            ] : [],
            (config('filament-logger.access.enabled') && config('filament-logger.access.color')) ? [
                config('filament-logger.access.color') => config('filament-logger.access.log_name'),
            ] : [],
            (config('filament-logger.notifications.enabled') &&  config('filament-logger.notifications.color')) ? [
                config('filament-logger.notifications.color') => config('filament-logger.notifications.log_name'),
            ] : [],
            $customs,
        );
    }
}

