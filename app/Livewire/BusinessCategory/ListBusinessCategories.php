<?php

namespace App\Livewire\BusinessCategory;

use App\Models\BusinessCategory;
use App\Models\DocumentType;
use App\Models\Feature;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
            ->query(BusinessCategory::query()->with('documentTypes'))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('feature_ids')
                    ->label('Features')
                    ->html()
                    ->formatStateUsing(function ($state, $record) {
                        // Get the raw feature_ids from the record
                        $featureIds = $record->feature_ids ?? [];
                        
                        // Ensure we always have an array
                        if (is_string($featureIds)) {
                            $featureIds = json_decode($featureIds, true) ?? [];
                        }
                        if (!is_array($featureIds)) {
                            $featureIds = [];
                        }
                        
                        // Convert feature IDs to feature names
                        $featureNames = collect($featureIds)
                            ->map(fn($id) => $this->features[$id] ?? null)
                            ->filter()
                            ->values()
                            ->toArray();
                        
                        if (empty($featureNames)) {
                            return '<span class="text-gray-400 text-sm italic">No features</span>';
                        }
                        
                        $totalFeatures = count($featureNames);
                        $maxVisible = 4;
                        $visibleFeatures = array_slice($featureNames, 0, $maxVisible);
                        $remaining = $totalFeatures - $maxVisible;
                        
                        $badges = collect($visibleFeatures)->map(function ($feature) {
                            return '<span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">' . e($feature) . '</span>';
                        })->join('');
                        
                        if ($remaining > 0) {
                            $allFeatures = implode(', ', array_map('e', $featureNames));
                            $badges .= '<span 
                                class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700 border border-gray-200 cursor-help" 
                                title="' . e($allFeatures) . '"
                                data-tooltip="' . e($allFeatures) . '"
                            >+' . $remaining . ' more</span>';
                        }
                        
                        return '<div class="flex flex-wrap gap-1 items-center" style="max-width: 400px;">' . $badges . '</div>';
                    })
                    ->description(function ($record) {
                        $featureIds = $record->feature_ids ?? [];
                        
                        // Ensure we always have an array
                        if (is_string($featureIds)) {
                            $featureIds = json_decode($featureIds, true) ?? [];
                        }
                        if (!is_array($featureIds)) {
                            $featureIds = [];
                        }
                        
                        $featureNames = collect($featureIds)
                            ->map(fn($id) => $this->features[$id] ?? null)
                            ->filter()
                            ->toArray();
                        
                        $count = count($featureNames);
                        return $count > 0 ? $count . ' feature' . ($count !== 1 ? 's' : '') : null;
                    })
                    ->wrap()
                    ->searchable(false)
                    ->sortable(false),
                Tables\Columns\TextColumn::make('documentTypes.name')
                    ->label('Documents')
                    ->badge()
                    ->limitList(3)
                    ->listWithLineBreaks()
                    ->default('—'),
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
                    ->fillForm(fn (BusinessCategory $record): array => [
                        'name' => $record->name,
                        'description' => $record->description,
                        'feature_ids' => $record->feature_ids,
                        'attached_documents' => $record->documentTypes->map(fn (DocumentType $documentType) => [
                            'document_type_id' => (string) $documentType->id,
                            'is_required' => (bool) $documentType->pivot->is_required,
                        ])->values()->toArray(),
                    ])
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
                        Repeater::make('attached_documents')
                            ->label('Required onboarding documents')
                            ->schema([
                                Select::make('document_type_id')
                                    ->label('Document type')
                                    ->options(DocumentType::active()->ordered()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Toggle::make('is_required')
                                    ->label('Required for this category')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->action(function (BusinessCategory $record, array $data): void {
                        $record->update([
                            'name' => $data['name'],
                            'description' => $data['description'] ?? null,
                            'feature_ids' => $data['feature_ids'] ?? [],
                        ]);

                        $this->syncCategoryDocuments($record, $data['attached_documents'] ?? []);

                        Notification::make()
                            ->title('Business Category updated successfully.')
                            ->success()
                            ->send();
                    }),
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
                        Repeater::make('attached_documents')
                            ->label('Required onboarding documents')
                            ->schema([
                                Select::make('document_type_id')
                                    ->label('Document type')
                                    ->options(DocumentType::active()->ordered()->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                Toggle::make('is_required')
                                    ->label('Required for this category')
                                    ->default(true),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->createAnother(false)
                    ->using(function (array $data): BusinessCategory {
                        $record = BusinessCategory::create([
                            'name' => $data['name'],
                            'description' => $data['description'] ?? null,
                            'feature_ids' => $data['feature_ids'] ?? [],
                        ]);

                        $this->syncCategoryDocuments($record, $data['attached_documents'] ?? []);

                        return $record;
                    })
                    ->after(function () {
                        Notification::make()
                            ->title('Business Category created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function syncCategoryDocuments(BusinessCategory $category, array $attachedDocuments): void
    {
        $sync = [];

        foreach ($attachedDocuments as $index => $item) {
            if (empty($item['document_type_id'])) {
                continue;
            }

            $sync[(int) $item['document_type_id']] = [
                'is_required' => ! empty($item['is_required']),
                'sort_order' => $index + 1,
            ];
        }

        $category->documentTypes()->sync($sync);
    }

    public function render(): View
    {
        return view('livewire.business-category.list-business-categories');
    }
}
