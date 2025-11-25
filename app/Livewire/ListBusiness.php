<?php

namespace App\Livewire;

use App\Models\Business;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;


class ListBusiness extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public array $features = [];

    public function mount()
    {
        $this->features = \App\Models\Feature::pluck('name', 'id')->toArray();
    }

    public function table(Table $table): Table
    {
        $query = Business::query()->latest();

        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    // ->defaultImageUrl(url('path/to/default/image.jpg'))
                    ,
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('businessCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('enabled_feature_ids')
                    ->label('Enabled Features')
                    ->getStateUsing(function ($record) {
                        $state = $record->enabled_feature_ids ?? [];
                        $state = is_array($state) ? $state : json_decode($state, true) ?? [];
                        return collect($state)->map(fn($id) => $this->features[$id] ?? null)->filter()->toArray();
                    })
                    ->badge(),
            ])
            ->filters([
                ...(Auth::check() && Auth::user()->business_id === 1 ? [
                    Tables\Filters\SelectFilter::make('name')
                        ->label('Business')
                        ->options(Business::pluck('name', 'id'))
                        ->searchable()
                        ->multiple(),
                ] : []),


            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn (): bool => Auth::user()->business_id === 1)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['account_number'] = (string) rand(1000000000, 9999999999);
                        return $data;
                    })
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('Enter business name'),
                        \Filament\Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->placeholder('Enter email address'),
                        \Filament\Forms\Components\TextInput::make('phone')
                            ->required()
                            ->placeholder('Enter phone number'),
                        \Filament\Forms\Components\TextInput::make('address')
                            ->required()
                            ->placeholder('Enter address'),
                        \Filament\Forms\Components\TextInput::make('country')
                            ->required()
                            ->placeholder('Enter country'),
                        \Filament\Forms\Components\TextInput::make('city')
                            ->required()
                            ->placeholder('Enter city/district/state'),
                        \Filament\Forms\Components\Select::make('business_category_id')
                            ->relationship('businessCategory', 'name')
                            ->reactive()
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                $set('enabled_feature_ids', []);
                            })
                            ->placeholder('Select category'),
                        \Filament\Forms\Components\CheckboxList::make('enabled_feature_ids')
                            ->label('Enabled Features')
                            ->options(function (\Filament\Forms\Get $get) {
                                $category = \App\Models\BusinessCategory::find($get('business_category_id'));
                                return \App\Models\Feature::whereIn('id', $category?->feature_ids ?? [])->pluck('name', 'id');
                            })
                            ->reactive(),
                        FileUpload::make('logo')
                            ->label('Business Logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'])
                            ->maxSize(2048) // 2MB in KB
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->helperText('Upload a logo for the business (max 2MB)'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('email')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('phone')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('address')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('country')
                            ->disabled(),
                        \Filament\Forms\Components\TextInput::make('city')
                            ->disabled(),
                        \Filament\Forms\Components\Select::make('business_category_id')
                            ->relationship('businessCategory', 'name')
                            ->disabled(),
                        \Filament\Forms\Components\CheckboxList::make('enabled_feature_ids')
                            ->label('Enabled Features')
                            ->options(function (\Filament\Forms\Get $get) {
                                $category = \App\Models\BusinessCategory::find($get('business_category_id'));
                                return \App\Models\Feature::whereIn('id', $category?->feature_ids ?? [])->pluck('name', 'id');
                            })
                            ->disabled(),
                    ]),
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('Enter business name'),
                        \Filament\Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->placeholder('Enter email address'),
                        \Filament\Forms\Components\TextInput::make('phone')
                            ->required()
                            ->placeholder('Enter phone number'),
                        \Filament\Forms\Components\TextInput::make('address')
                            ->required()
                            ->placeholder('Enter address'),
                        \Filament\Forms\Components\TextInput::make('country')
                            ->required()
                            ->placeholder('Enter country'),
                        \Filament\Forms\Components\TextInput::make('city')
                            ->required()
                            ->placeholder('Enter city/district/state'),
                        \Filament\Forms\Components\Select::make('business_category_id')
                            ->relationship('businessCategory', 'name')
                            ->reactive()
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                $set('enabled_feature_ids', []);
                            })
                            ->placeholder('Select category'),
                        \Filament\Forms\Components\CheckboxList::make('enabled_feature_ids')
                            ->label('Enabled Features')
                            ->options(function (\Filament\Forms\Get $get) {
                                $category = \App\Models\BusinessCategory::find($get('business_category_id'));
                                return \App\Models\Feature::whereIn('id', $category?->feature_ids ?? [])->pluck('name', 'id');
                            })
                            ->reactive(),
                        FileUpload::make('logo')
                            ->label('Business Logo')
                            ->image()
                            ->directory('logos')
                            ->disk('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'])
                            ->maxSize(2048) // 2MB in KB
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->helperText('Upload a logo for the business (max 2MB)'),
                    ])
                    ->visible(fn (Business $record): bool => Auth::user()->business_id === 1 || $record->id === Auth::user()->business_id),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Business $record): bool => Auth::user()->business_id === 1),

                Tables\Actions\Action::make('update_logo')
                    ->label('Update Logo')
                    ->modalHeading('Update Business Logo')
                    ->modalSubmitActionLabel('Save')
                    ->form([
                        FileUpload::make('logo')
                            ->label('Upload Logo')
                            ->image()
                            ->preserveFilenames()
                            ->directory('logos')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                            ->maxSize(1024) // 1MB in KB
                            ->required()

                    ])
                    ->action(function (Business $record, array $data): void {
                        if (Auth::user()->business_id === 1 || $record->id === Auth::user()->business_id) {
                            if (!empty($data['logo'])) {
                                Log::info('Uploaded file data:', ['logo' => $data['logo']]);
                                $record->update(['logo' => $data['logo']]);
                                Notification::make()
                                    ->title('Logo Updated')
                                    ->success()
                                    ->body('The business logo was successfully updated.')
                                    ->send();
                            } else {
                                Log::error('No logo file provided in the upload.');
                                Notification::make()
                                    ->title('Upload Failed')
                                    ->danger()
                                    ->body('No file was uploaded.')
                                    ->send();
                            }
                        } else {
                            abort(403, 'Unauthorized action.');
                        }
                    })

                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn(Business $record): bool => Auth::user()->business_id === 1),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([...])
            ]);
    }

    public function render(): View
    {
        return view('livewire.list-business');
    }
}
