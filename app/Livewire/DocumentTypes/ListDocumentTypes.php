<?php

namespace App\Livewire\DocumentTypes;

use App\Models\DocumentType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListDocumentTypes extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(DocumentType::query()->ordered())
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->description(fn (DocumentType $record): ?string => $record->description)
                    ->wrap(),
                Tables\Columns\TextColumn::make('account_type')
                    ->label('Account Type')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'individual' => 'Individual',
                        'both' => 'Both',
                        default => 'Business',
                    })
                    ->badge(),
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Required')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Document Type')
                    ->form($this->documentTypeForm()),
                DeleteAction::make()
                    ->modalHeading('Delete Document Type'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Document Type')
                    ->modalHeading('Add Document Type')
                    ->form($this->documentTypeForm())
                    ->createAnother(false)
                    ->after(function () {
                        Notification::make()
                            ->title('Document type created successfully.')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No document types yet')
            ->emptyStateDescription('Add document types that can be required during business onboarding.');
    }

    protected function documentTypeForm(): array
    {
        return [
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            Textarea::make('description')
                ->rows(3)
                ->columnSpanFull(),
            Select::make('account_type')
                ->label('Account Type')
                ->options([
                    'business' => 'Business',
                    'individual' => 'Individual',
                    'both' => 'Both',
                ])
                ->required()
                ->default('business'),
            Toggle::make('is_required')
                ->label('Required by default')
                ->default(false),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            TextInput::make('sort_order')
                ->label('Display order')
                ->numeric()
                ->default(0)
                ->minValue(0),
        ];
    }

    public function render(): View
    {
        return view('livewire.document-types.list-document-types');
    }
}
