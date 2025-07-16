<?php

namespace App\Livewire\Documents;

use App\Models\BusinessDocument;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class ListBusinessDocuments extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public function table(Table $table): Table
    {

        $query = BusinessDocument::query()->latest();


        // Restrict to user's business unless admin
        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('title')
                    ->label('Document Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->limit(40)
                    ->label('Description')
                    ->toggleable(),

                TextColumn::make('file_path')
                    ->label('Download')
                    ->formatStateUsing(function ($state) {
                        if ($state && Storage::disk('public')->exists($state)) {
                            $url = Storage::disk('public')->url($state);
                            return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Download</a>";
                        }
                        return 'No File';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Uploaded By')
                    ->sortable(),

                TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->label('Status')
                    ->multiple()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'name')
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->visible(fn () => Auth::check() && Auth::user()->business_id === 1), // Only visible to admin
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(BusinessDocument $record) => route('business-documents.show', $record)),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn(BusinessDocument $record) => route('business-documents.edit', $record))
                    ->visible(fn() => Auth::check() && Auth::user()->business_id === 1),

                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-check-badge')
                    ->form([
                        Select::make('status')
                            ->label('New Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Status Update')
                    ->modalDescription('Are you sure you want to change the document status?')
                    ->modalSubmitActionLabel('Yes, Update')
                    ->action(function (BusinessDocument $record, array $data) {
                        $record->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('Status Updated')
                            ->body('Document status updated to: ' . ucfirst($data['status']))
                            ->success()
                            ->send();
                    })
                    ->visible(fn() => Auth::check() && Auth::user()->business_id === 1),
            ])
            ->bulkActions([]);
    }

    public function render(): View
    {
        return view('livewire.documents.list-business-documents');
    }
}
