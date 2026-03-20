<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ParentGuardian;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ParentGuardianManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = ParentGuardian::query()
            ->with(['students.classRoom']);
        
        // Filter by business_id for non-admin users
        if (auth()->user()->business_id !== 1) {
            $query->where('business_id', auth()->user()->business_id);
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

                        return asset('storage/' . ltrim($record->photo, '/'));
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
                            return $student->full_name . ' (' . $className . ')';
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
                    ->label('Business')
                    ->sortable(),
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
            ->actions([
                Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('primary')
                    ->url(fn (ParentGuardian $record): string => route('school-management.parents.edit', $record))
                    ->visible(fn (ParentGuardian $record): bool => auth()->user()->business_id === 1 || $record->business_id === auth()->user()->business_id),
                DeleteAction::make()
                    ->modalHeading('Delete Parent/Guardian')
                    ->action(function (ParentGuardian $record): void {
                        DB::transaction(function () use ($record) {
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
                    ->successNotificationTitle('Parent/Guardian deleted permanently.'),
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
