<?php

namespace App\Livewire;

use App\Models\LoginAdvert;
use App\Support\TenantScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Livewire\Component;

class LoginAdvertManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        abort_unless(TenantScope::isSuperAdmin(), 403);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(LoginAdvert::query())
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->height(40)
                    ->square()
                    ->extraImgAttributes(['style' => 'object-fit: contain;']),
                Tables\Columns\TextColumn::make('advertiser_name')
                    ->label('Advertiser')
                    ->searchable(),
                Tables\Columns\TextColumn::make('creative_type')
                    ->label('Creative')
                    ->formatStateUsing(fn (?string $state) => $state === 'video' ? 'Video' : 'Image'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (LoginAdvert $record) => $record->scheduleLabel()),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Starts')
                    ->formatStateUsing(fn ($state, LoginAdvert $record) => $this->formatInTimezone($record->starts_at, $record->timezone)),
                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends')
                    ->formatStateUsing(fn ($state, LoginAdvert $record) => $this->formatInTimezone($record->ends_at, $record->timezone)),
                Tables\Columns\TextColumn::make('timezone')
                    ->label('Timezone'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New login advert')
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(fn (array $data) => $this->prepare($data))
                    ->using(function (array $data): LoginAdvert {
                        $data['created_by'] = auth()->id();

                        return LoginAdvert::query()->create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->modalHeading('Advert preview')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (LoginAdvert $record) => view('livewire.login-advert-preview', [
                        'advert' => $record,
                    ])),
                Tables\Actions\EditAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(fn (array $data) => $this->prepare($data)),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public function render()
    {
        return view('livewire.login-advert-management');
    }

    private function formSchema(): array
    {
        return [
            TextInput::make('advertiser_name')
                ->label('Advertiser name')
                ->required()
                ->maxLength(120),
            FileUpload::make('logo_path')
                ->label('Advertiser logo')
                ->disk('public')
                ->directory('login-adverts/logos')
                ->image()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(2048)
                ->helperText('Shown as supplied. The app fits the logo without stretching it.'),
            Select::make('creative_type')
                ->label('Creative')
                ->options([
                    'image' => 'Image',
                    'video' => 'Video',
                ])
                ->required()
                ->reactive()
                ->default('image'),
            FileUpload::make('creative_path')
                ->label('Creative file')
                ->disk('public')
                ->directory('login-adverts/creatives')
                ->required()
                ->acceptedFileTypes(fn (Get $get): array => $get('creative_type') === 'video'
                    ? ['video/mp4', 'video/quicktime']
                    : ['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(fn (Get $get): int => $get('creative_type') === 'video' ? 20480 : 5120),
            TextInput::make('destination_url')
                ->label('Destination link')
                ->url()
                ->maxLength(500)
                ->helperText('Optional. Stored with the advert. The sign-in screen still opens the dashboard after 10 seconds.'),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            Select::make('timezone')
                ->label('Schedule timezone')
                ->options(self::timezoneOptions())
                ->default('Africa/Nairobi')
                ->required()
                ->reactive(),
            DateTimePicker::make('starts_at')
                ->label('Starts')
                ->required()
                ->seconds(false)
                ->timezone(fn (Get $get): string => $get('timezone') ?: 'Africa/Nairobi'),
            DateTimePicker::make('ends_at')
                ->label('Ends')
                ->required()
                ->seconds(false)
                ->timezone(fn (Get $get): string => $get('timezone') ?: 'Africa/Nairobi')
                ->after('starts_at'),
        ];
    }

    private function prepare(array $data): array
    {
        $data['logo_path'] = $this->storedPath($data['logo_path'] ?? null);
        $data['creative_path'] = $this->storedPath($data['creative_path'] ?? null);
        $data['destination_url'] = filled($data['destination_url'] ?? null) ? $data['destination_url'] : null;
        $data['timezone'] = $data['timezone'] ?: 'Africa/Nairobi';
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function storedPath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return filled($value) ? (string) $value : null;
    }

    private function formatInTimezone(mixed $value, ?string $timezone): string
    {
        if (! $value) {
            return '—';
        }

        return Carbon::parse($value)->timezone($timezone ?: 'Africa/Nairobi')->format('d M Y H:i');
    }

    public static function timezoneOptions(): array
    {
        $zones = [
            'Africa/Nairobi',
            'Africa/Kampala',
            'Africa/Dar_es_Salaam',
            'Africa/Lagos',
            'Africa/Johannesburg',
            'Europe/London',
            'America/New_York',
            'UTC',
        ];

        return array_combine($zones, $zones);
    }
}
