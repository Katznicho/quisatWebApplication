<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ParentGuardian;
use App\Models\User;
use App\Services\ParentUniversalCodeService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ParentGuardianManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = ParentGuardian::query()
            ->with(['students.classRoom', 'memberships.business']);

        // Include parents linked through the multi-business membership table.
        if (auth()->user()->business_id !== 1) {
            $businessId = (int) auth()->user()->business_id;
            $query->where(function (Builder $query) use ($businessId): void {
                $query->where('business_id', $businessId)
                    ->orWhereHas('memberships', function (Builder $membership) use ($businessId): void {
                        $membership
                            ->where('business_id', $businessId)
                            ->where('status', 'active');
                    });
            });
        }

        return $table
            ->query($query)
            ->searchable()
            ->columns([
                ImageColumn::make('photo')
                    ->label('Profile Photo')
                    ->circular()
                    ->getStateUsing(function (ParentGuardian $record): ?string {
                        if (empty($record->photo)) {
                            return null;
                        }

                        return asset('storage/'.ltrim($record->photo, '/'));
                    })
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Parent&background=007AFF&color=ffffff&size=128'),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('relationship')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('occupation')
                    ->searchable(),
                Tables\Columns\TextColumn::make('students')
                    ->label('Children')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->students->isEmpty()) {
                            return 'No children';
                        }

                        return $record->students->map(function ($student) {
                            $className = $student->classRoom?->name ?? 'Not Assigned';

                            return $student->full_name.' ('.$className.')';
                        })->join(', ');
                    })
                    ->wrap()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('students', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhereHas('classRoom', function ($classQuery) use ($search) {
                                    $classQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                    }),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Primary business')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('account_type')
                    ->label('Account')
                    ->colors([
                        'warning' => 'guest',
                        'success' => 'linked',
                    ]),
                Tables\Columns\TextColumn::make('universal_code')
                    ->label('Quisat code')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
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
            ->headerActions([
                Action::make('link_by_quisat_code')
                    ->label('Link by Quisat code')
                    ->icon('heroicon-o-link')
                    ->form([
                        TextInput::make('universal_code')
                            ->label('Quisat code')
                            ->placeholder('QSP-XXXXXXXX')
                            ->required()
                            ->maxLength(32),
                        Select::make('relationship')
                            ->options([
                                'father' => 'Father',
                                'mother' => 'Mother',
                                'guardian' => 'Guardian',
                                'other' => 'Other',
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $businessId = (int) auth()->user()->business_id;
                        if ($businessId < 1) {
                            Notification::make()
                                ->danger()
                                ->title('Your account is not linked to a business.')
                                ->send();

                            return;
                        }

                        $codes = app(ParentUniversalCodeService::class);
                        $parent = $codes->findByCode((string) $data['universal_code']);

                        if (! $parent) {
                            Notification::make()
                                ->danger()
                                ->title('Quisat code not found.')
                                ->send();

                            return;
                        }

                        $codes->attachToBusiness(
                            $parent,
                            $businessId,
                            'universal_code',
                            $data['relationship'] ?? null,
                        );

                        Notification::make()
                            ->success()
                            ->title($parent->full_name.' linked successfully.')
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn (ParentGuardian $record): string => route('school-management.parents.edit', $record))
                    ->visible(fn (ParentGuardian $record): bool => (int) auth()->user()->business_id === 1
                        || $record->belongsToBusiness((int) auth()->user()->business_id)),
                DeleteAction::make()
                    ->modalHeading(fn (): string => (int) auth()->user()->business_id === 1
                        ? 'Delete Parent/Guardian'
                        : 'Remove Parent/Guardian from this business')
                    ->action(function (ParentGuardian $record): void {
                        DB::transaction(function () use ($record) {
                            $businessId = (int) auth()->user()->business_id;

                            if ($businessId !== 1) {
                                $record->memberships()->where('business_id', $businessId)->delete();

                                if ((int) $record->business_id === $businessId) {
                                    $nextBusinessId = $record->memberships()
                                        ->where('status', 'active')
                                        ->value('business_id');

                                    $record->forceFill([
                                        'business_id' => $nextBusinessId,
                                        'account_type' => $nextBusinessId ? 'linked' : 'guest',
                                    ])->save();
                                }

                                return;
                            }

                            // Remove linked login account(s) by email, then permanently remove parent.
                            $linkedUsers = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $record->email))])->get();
                            foreach ($linkedUsers as $linkedUser) {
                                $linkedUser->tokens()->delete();
                                $linkedUser->delete();
                            }

                            $record->tokens()->delete();
                            $record->forceDelete();
                        });
                    })
                    ->successNotificationTitle(fn (): string => (int) auth()->user()->business_id === 1
                        ? 'Parent/Guardian deleted permanently.'
                        : 'Parent/Guardian removed from this business.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete_permanently')
                        ->label('Delete Permanently')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-trash')
                        ->action(function ($records): void {
                            DB::transaction(function () use ($records) {
                                foreach ($records as $record) {
                                    $businessId = (int) auth()->user()->business_id;

                                    if ($businessId !== 1) {
                                        $record->memberships()->where('business_id', $businessId)->delete();

                                        if ((int) $record->business_id === $businessId) {
                                            $nextBusinessId = $record->memberships()
                                                ->where('status', 'active')
                                                ->value('business_id');

                                            $record->forceFill([
                                                'business_id' => $nextBusinessId,
                                                'account_type' => $nextBusinessId ? 'linked' : 'guest',
                                            ])->save();
                                        }

                                        continue;
                                    }

                                    $linkedUsers = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $record->email))])->get();
                                    foreach ($linkedUsers as $linkedUser) {
                                        $linkedUser->tokens()->delete();
                                        $linkedUser->delete();
                                    }

                                    $record->tokens()->delete();
                                    $record->forceDelete();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.parent-guardian-management');
    }
}
