<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ClassRoom;
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

class ClassRoomManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = ClassRoom::query();
        
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
                Tables\Columns\TextColumn::make('capacity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
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
                    ->modalHeading('Edit Classroom')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter classroom name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter classroom code'),
                        TextInput::make('capacity')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter capacity'),
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->label('Branch')
                            ->placeholder('Select branch (optional)'),
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
                    ->successNotificationTitle('Classroom updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Classroom')
                    ->successNotificationTitle('Classroom deleted successfully (soft).'),
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
                    ->label('Add Classroom')
                    ->modalHeading('Add New Classroom')
                    ->form([
                        Hidden::make('business_id')
                            ->default(auth()->user()->business_id),
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter classroom name'),
                        TextInput::make('code')
                            ->required()
                            ->placeholder('Enter classroom code'),
                        TextInput::make('capacity')
                            ->numeric()
                            ->required()
                            ->placeholder('Enter capacity'),
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->label('Branch')
                            ->placeholder('Select branch (optional)'),
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
                    ->after(function (ClassRoom $record) {
                        Notification::make()
                            ->title('Classroom created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.class-room-management');
    }
}
