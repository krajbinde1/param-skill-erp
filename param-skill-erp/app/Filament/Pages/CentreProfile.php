<?php

namespace App\Filament\Pages;

use App\Enums\RoleName;
use App\Models\Centre;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CentreProfile extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Centre Profile';

    protected static ?string $title = 'Centre Profile';

    protected static string|\UnitEnum|null $navigationGroup = 'Centre Management';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.centre-profile';

    public ?Centre $centre = null;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) $user?->hasRole(RoleName::CentreManager->value);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function mount(): void
    {
        $user = auth()->user();
        $this->centre = $user?->centre;

        if ($this->centre === null) {
            return;
        }

        $this->form->fill([
            'address' => $this->centre->address,
            'village_city' => $this->centre->village_city,
            'taluka' => $this->centre->taluka,
            'district' => $this->centre->district,
            'pincode' => $this->centre->pincode,
            'latitude' => $this->centre->latitude,
            'longitude' => $this->centre->longitude,
            'centre_photo' => $this->centre->centre_photo,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Editable details')
                    ->schema([
                        Textarea::make('address')->required()->rows(2)->columnSpanFull(),
                        TextInput::make('village_city')->label('Village / City'),
                        TextInput::make('taluka'),
                        TextInput::make('district')->required(),
                        TextInput::make('pincode')->label('PIN Code')->maxLength(6)->rule('regex:/^\d{6}$/'),
                        TextInput::make('latitude')->numeric()->minValue(-90)->maxValue(90),
                        TextInput::make('longitude')->numeric()->minValue(-180)->maxValue(180),
                        FileUpload::make('centre_photo')
                            ->image()
                            ->disk('public')
                            ->directory('centres/photos')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if ($this->centre === null) {
            return;
        }

        $state = $this->form->getState();

        $this->centre->update([
            ...$state,
            'updated_by' => auth()->id(),
        ]);

        Notification::make()->title('Centre profile updated')->success()->send();
    }

    public function downloadAgreement(): ?StreamedResponse
    {
        if ($this->centre?->agreement_document === null) {
            Notification::make()->title('No agreement document available')->warning()->send();

            return null;
        }

        return Storage::disk('private')->download($this->centre->agreement_document);
    }
}
