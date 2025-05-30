<?php

namespace App\Filament\Pages\Tenancy;

use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Filament\Forms;

class FactoryProfile Extends EditTenantProfile
{

    public static function getLabel(): string
    {
        return 'Factory profile';
    }

    protected ?string $maxWidth = '3xl';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Factory Information')
                        ->schema([
                            Forms\Components\FileUpload::make('logo')
                                ->label('')
                                ->alignCenter()
                                ->avatar()
                                ->directory('company'),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('slogan')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255),
                        ]),
                    Forms\Components\Wizard\Step::make('Factory Details')
                        ->schema([
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('address')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('website')
                                ->maxLength(255),

                            Forms\Components\Toggle::make('is_active')
                                ->required(),
                        ])
                ])->submitAction(new HtmlString(Blade::render(<<<BLADE
                    <x-filament::button
                        type="submit"
                        size="sm"
                        wire:submit="register"
                    >
                        Save changes
                    </x-filament::button>
                    BLADE))),


            ]);
    }
    protected function getFormActions(): array
    {
        return [];
    }
}
