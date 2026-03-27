<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Student;
use App\Models\ParentGuardian;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StudentManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = Student::query();
        
        // Filter by business_id for non-admin users
        if (auth()->user()->business_id !== 1) {
            $query->where('business_id', auth()->user()->business_id);
        }
        
        return $table
            ->query($query)
            ->columns([
                ImageColumn::make('photo')
                    ->label('Profile Photo')
                    ->circular()
                    ->getStateUsing(function (Student $record): ?string {
                        if (empty($record->photo)) {
                            return null;
                        }

                        return asset('storage/' . ltrim($record->photo, '/'));
                    })
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Student&background=007AFF&color=ffffff&size=128'),
                // Tables\Columns\TextColumn::make('uuid')
                //     ->label('Student ID')
                //     ->searchable(),
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
                Tables\Columns\TextColumn::make('student_id')
                    ->label('Student ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('classRoom.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parentGuardian.full_name')
                    ->label('Parent/Guardian')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'graduated',
                        'info' => 'transferred',
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
                    ->url(fn (Student $record): string => route('school-management.students.edit', $record))
                    ->visible(fn (Student $record): bool => auth()->user()->business_id === 1 || $record->business_id === auth()->user()->business_id),
                DeleteAction::make()
                    ->modalHeading('Delete Student')
                    ->action(function (Student $record): void {
                        DB::transaction(function () use ($record) {
                            // Remove any linked login account using same email, then permanently remove student.
                            if (!empty($record->email)) {
                                $linkedUsers = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $record->email))])->get();
                                foreach ($linkedUsers as $linkedUser) {
                                    $linkedUser->tokens()->delete();
                                    $linkedUser->delete();
                                }
                            }

                            $record->forceDelete();
                        });
                    })
                    ->successNotificationTitle('Student deleted permanently.'),
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
                                    if (!empty($record->email)) {
                                        $linkedUsers = User::whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim((string) $record->email))])->get();
                                        foreach ($linkedUsers as $linkedUser) {
                                            $linkedUser->tokens()->delete();
                                            $linkedUser->delete();
                                        }
                                    }

                                    $record->forceDelete();
                                }
                            });
                        }),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.student-management');
    }
}
