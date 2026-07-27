<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ChangePassword extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'Change Password';

    protected static ?string $title = 'Change Password';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 90;

    protected static bool $shouldRegisterNavigation = true;

    protected string $view = 'filament.pages.change-password';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Update password')
                    ->description('Choose a strong password. You must change a temporary password before continuing.')
                    ->schema([
                        TextInput::make('current_password')
                            ->label('Current password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->currentPassword(),
                        TextInput::make('password')
                            ->label('New password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->rule(Password::min(10)->mixedCase()->numbers()->symbols())
                            ->different('current_password')
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirm new password')
                            ->password()
                            ->revealable()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $user = auth()->user();

        if ($user === null) {
            return;
        }

        if (! Hash::check($state['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'The current password is incorrect.',
            ]);
        }

        if (Hash::check($state['password'], $user->password)) {
            throw ValidationException::withMessages([
                'data.password' => 'The new password must be different from your current password.',
            ]);
        }

        $user->forceFill([
            'password' => $state['password'],
            'must_change_password' => false,
        ])->save();

        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->log('password_changed');

        Notification::make()
            ->title('Password updated')
            ->success()
            ->send();

        $this->redirect(filament()->getUrl());
    }
}
