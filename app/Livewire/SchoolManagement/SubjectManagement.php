<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Subject;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
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

class SubjectManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = Subject::query();
        
        // Filter by business_id for non-admin users
        if (auth()->user()->business_id !== 1) {
            $query->where('business_id', auth()->user()->business_id);
        }
        
        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->wrap(),
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
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Subject')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter subject name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter subject code'),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->successNotificationTitle('Subject updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Subject')
                    ->successNotificationTitle('Subject deleted successfully (soft).'),
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
                    ->label('Add Subject')
                    ->modalHeading('Add New Subject')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter subject name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter subject code'),
                        Textarea::make('description')
                            ->placeholder('Enter description')
                            ->rows(3),
                        Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->createAnother(false)
                    ->after(function (Subject $record) {
                        Notification::make()
                            ->title('Subject created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.subject-management');
    }
}
