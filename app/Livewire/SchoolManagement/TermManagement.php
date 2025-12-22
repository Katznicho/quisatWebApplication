<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Term;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TermManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Term::query()->where('business_id', Auth::user()->business_id))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('academic_year')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_weeks')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'upcoming',
                        'info' => 'completed',
                    ]),
                Tables\Columns\IconColumn::make('is_current_term')
                    ->label('Current Term')
                    ->boolean(),
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
                    ->modalHeading('Edit Term')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter term name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter term code'),
                        TextInput::make('academic_year')
                            ->required()
                            ->placeholder('Enter academic year (e.g., 2023)'),
                        TextInput::make('academic_year_start')
                            ->label('Academic Year Start')
                            ->numeric()
                            ->required()
                            ->placeholder('e.g., 2023'),
                        TextInput::make('academic_year_end')
                            ->label('Academic Year End')
                            ->numeric()
                            ->required()
                            ->placeholder('e.g., 2024'),
                        Select::make('term_type')
                            ->label('Term Type')
                            ->options([
                                'first' => 'First Term',
                                'second' => 'Second Term',
                                'third' => 'Third Term',
                                'summer' => 'Summer Term',
                                'holiday' => 'Holiday',
                                'other' => 'Other',
                            ])
                            ->required(),
                        DatePicker::make('start_date')
                            ->required(),
                        DatePicker::make('end_date')
                            ->required(),
                        TextInput::make('duration_weeks')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter duration in weeks'),
                        TextInput::make('total_instructional_days')
                            ->label('Total Instructional Days')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter total instructional days'),
                        TextInput::make('total_instructional_hours')
                            ->label('Total Instructional Hours')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter total instructional hours'),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Toggle::make('is_current_term')
                            ->label('Set as Current Term')
                            ->default(false),
                        Toggle::make('is_next_term')
                            ->label('Set as Next Term')
                            ->default(false),
                    ])
                    ->successNotificationTitle('Term updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Term')
                    ->successNotificationTitle('Term deleted successfully (soft).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Term')
                    ->modalHeading('Add New Term')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter term name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter term code'),
                        TextInput::make('academic_year')
                            ->required()
                            ->placeholder('Enter academic year (e.g., 2023)'),
                        TextInput::make('academic_year_start')
                            ->label('Academic Year Start')
                            ->numeric()
                            ->required()
                            ->default(2023)
                            ->placeholder('e.g., 2023'),
                        TextInput::make('academic_year_end')
                            ->label('Academic Year End')
                            ->numeric()
                            ->required()
                            ->default(2024)
                            ->placeholder('e.g., 2024'),
                        Select::make('term_type')
                            ->label('Term Type')
                            ->options([
                                'first' => 'First Term',
                                'second' => 'Second Term',
                                'third' => 'Third Term',
                                'summer' => 'Summer Term',
                                'holiday' => 'Holiday',
                                'other' => 'Other',
                            ])
                            ->required()
                            ->default('first'),
                        DatePicker::make('start_date')
                            ->required(),
                        DatePicker::make('end_date')
                            ->required(),
                        TextInput::make('duration_weeks')
                            ->numeric()
                            ->required()
                            ->default(12)
                            ->placeholder('Enter duration in weeks'),
                        TextInput::make('total_instructional_days')
                            ->label('Total Instructional Days')
                            ->numeric()
                            ->required()
                            ->default(90)
                            ->placeholder('Enter total instructional days'),
                        TextInput::make('total_instructional_hours')
                            ->label('Total Instructional Hours')
                            ->numeric()
                            ->required()
                            ->default(180)
                            ->placeholder('Enter total instructional hours'),
                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Active',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('draft'),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Toggle::make('is_current_term')
                            ->label('Set as Current Term')
                            ->default(false),
                        Toggle::make('is_next_term')
                            ->label('Set as Next Term')
                            ->default(false),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['business_id'] = Auth::user()->business_id;
                        $data['created_by'] = Auth::id();
                        return $data;
                    })
                    ->createAnother(false)
                    ->after(function (Term $record) {
                        Notification::make()
                            ->title('Term created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.term-management');
    }
}
