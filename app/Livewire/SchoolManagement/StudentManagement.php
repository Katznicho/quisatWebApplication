<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Student;
use App\Models\ParentGuardian;
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
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

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
                Tables\Columns\TextColumn::make('class_room.name')
                    ->label('Class')
                    ->sortable(),
                Tables\Columns\TextColumn::make('parent_guardian.full_name')
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
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Student')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('first_name')
                            ->required()
                            ->placeholder('Enter first name'),
                        TextInput::make('last_name')
                            ->required()
                            ->placeholder('Enter last name'),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->placeholder('Enter email address'),
                        TextInput::make('phone')
                            ->tel()
                            ->placeholder('Enter phone number'),
                        TextInput::make('student_id')
                            ->required()
                            ->placeholder('Enter student ID'),
                        DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->required(),
                        Select::make('gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                            ])
                            ->required(),
                        DatePicker::make('admission_date')
                            ->label('Admission Date')
                            ->required(),
                        Select::make('class_room_id')
                            ->relationship('classRoom', 'name')
                            ->label('Class')
                            ->placeholder('Select class (optional)'),
                        Select::make('parent_guardian_id')
                            ->options(function () {
                                $query = ParentGuardian::query();
                                if (auth()->user()->business_id !== 1) {
                                    $query->where('business_id', auth()->user()->business_id);
                                }
                                return $query->get()->pluck('full_name', 'id');
                            })
                            ->label('Parent/Guardian')
                            ->required()
                            ->searchable(),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'graduated' => 'Graduated',
                                'transferred' => 'Transferred',
                            ])
                            ->default('active')
                            ->required(),
                        Textarea::make('address')
                            ->placeholder('Enter address')
                            ->rows(3),
                    ])
                    ->successNotificationTitle('Student updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Student')
                    ->successNotificationTitle('Student deleted successfully (soft).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.student-management');
    }
}
