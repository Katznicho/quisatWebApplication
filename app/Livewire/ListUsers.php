<?php

namespace App\Livewire;

use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use App\Models\Business;
use Illuminate\Support\Facades\Auth;

class ListUsers extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = User::query()->latest()->with('business');

        // Restrict users based on authenticated user's business_id
        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\ImageColumn::make('profile_photo_url')
                    ->label('Profile Photo')
                    ->circular()
                    ->defaultImageUrl(url('path/to/default/image.jpg')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.name')
                    ->label('Role')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('email_verified_at')
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
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                ...(Auth::check() && Auth::user()->business_id === 1 ? [
                    Tables\Filters\SelectFilter::make('business')
                        ->relationship('business', 'name')
                        ->label('Business')
                        ->preload()
                        ->searchable()
                        ->multiple(),
                ] : []),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->label('Status')
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form([
                        \Filament\Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('Enter full name'),
                        \Filament\Forms\Components\TextInput::make('email')
                            ->required()
                            ->email()
                            ->placeholder('Enter email address'),
                        \Filament\Forms\Components\Select::make('business_id')
                            ->relationship('business', 'name')
                            ->visible(fn () => Auth::user()->business_id === 1)
                            ->reactive()
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, $state) {
                                $set('role_id', null);
                            })
                            ->placeholder('Select a business'),
                        \Filament\Forms\Components\Select::make('role_id')
                            ->label('Role')
                            ->options(function (\Filament\Forms\Get $get) {
                                $businessId = $get('business_id') ?? Auth::user()->business_id;
                                return \App\Models\Role::where('business_id', $businessId)->pluck('name', 'id');
                            })
                            ->reactive()
                            ->required()
                            ->placeholder('Select a role'),
                        \Filament\Forms\Components\Select::make('status')
                            ->options(['active' => 'Active', 'inactive' => 'Inactive', 'suspended' => 'Suspended'])
                            ->required()
                            ->placeholder('Select status'),
                    ])
                    ->visible(fn (User $record): bool => Auth::user()->business_id === 1 || $record->business_id === Auth::user()->business_id),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (User $record): bool => Auth::user()->business_id === 1 || $record->business_id === Auth::user()->business_id),
                Tables\Actions\Action::make('update_status')
                    ->label('Change Status')
                    ->form([
                        \Filament\Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'suspended' => 'Suspended',
                            ])
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        if (Auth::user()->business_id === 1 || $record->business_id === Auth::user()->business_id) {
                            $record->update(['status' => $data['status']]);
                        } else {
                            abort(403, 'Unauthorized action.');
                        }
                    })
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->visible(fn (User $record): bool => Auth::user()->business_id === 1 || $record->business_id === Auth::user()->business_id),
                Tables\Actions\Action::make('impersonate')
                    ->label('Impersonate')
                    ->url(fn (User $record): string => route('impersonate', $record->id))
                    ->color('warning')
                    ->icon('heroicon-o-user')
                    ->visible(fn (User $record): bool => Auth::user()->business_id === 1 && Auth::user()->id !== $record->id)
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_status_bulk')
                        ->label('Update Status')
                        ->form([
                            \Filament\Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'inactive' => 'Inactive',
                                    'suspended' => 'Suspended',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $records, array $data): void {
                            $userIds = collect($records)->filter(function ($recordId) {
                                $user = User::find($recordId);
                                return Auth::user()->business_id === 1 || $user->business_id === Auth::user()->business_id;
                            })->pluck('id');

                            if ($userIds->isNotEmpty()) {
                                User::whereIn('id', $userIds)->update(['status' => $data['status']]);
                            } else {
                                abort(403, 'Unauthorized action.');
                            }
                        })
                        ->icon('heroicon-o-pencil')
                        ->color('primary'),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.list-users');
    }
}