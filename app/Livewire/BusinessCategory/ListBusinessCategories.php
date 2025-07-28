<?php

namespace App\Livewire\BusinessCategory;

use App\Models\BusinessCategory;
use App\Models\Feature;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Filters\TrashedFilter;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessCategories extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public array $features = [];

    public function mount()
    {
        $this->features = Feature::pluck('name', 'id')->toArray();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BusinessCategory::query())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('feature_ids')
                    ->label('Features')
                    ->getStateUsing(function ($record) {
                        $state = $record->feature_ids ?? [];
                        return collect($state)->map(fn($id) => $this->features[$id] ?? null)->filter()->toArray();
                    })
                    ->badge(),
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
                ViewAction::make()
                    ->modalHeading('View Business Category')
                    ->form([
                        TextInput::make('name')
                            ->disabled(),
                        Textarea::make('description')
                            ->disabled(),
                        CheckboxList::make('feature_ids')
                            ->options(Feature::pluck('name', 'id'))
                            ->disabled(),
                    ]),
                EditAction::make()
                    ->modalHeading('Edit Business Category')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter category name'),
                        Textarea::make('description')
                            ->nullable()
                            ->placeholder('Enter description'),
                        CheckboxList::make('feature_ids')
                            ->label('Features')
                            ->options(Feature::pluck('name', 'id')),
                    ])
                    ->successNotificationTitle('Business Category updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Business Category')
                    ->successNotificationTitle('Business Category deleted successfully (soft).'),
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
                    ->label('Create Business Category')
                    ->modalHeading('Add New Business Category')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->placeholder('Enter category name'),
                        Textarea::make('description')
                            ->nullable()
                            ->placeholder('Enter description'),
                        CheckboxList::make('feature_ids')
                            ->label('Features')
                            ->options(Feature::pluck('name', 'id')),
                    ])
                    ->createAnother(false)
                    ->after(function (BusinessCategory $record) {
                        Notification::make()
                            ->title('Business Category created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.business-category.list-business-categories');
    }
}
