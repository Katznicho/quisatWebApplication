<?php

namespace App\Livewire\ServicePoints;

use App\Models\ServicePoint;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListServicePoints extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = ServicePoint::query()->latest();

        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service Point')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                ...(Auth::check() && Auth::user()->business_id === 1 ? [
                    Tables\Filters\SelectFilter::make('business_id')
                        ->label('Filter by Business')
                        ->options(Business::pluck('name', 'id'))
                        ->searchable()
                        ->multiple(),
                ] : []),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Service Point')
                    ->form(fn(ServicePoint $record) => [
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->placeholder('Select a business')
                            ->options(Business::pluck('name', 'id'))
                            ->required()
                            ->disabled(fn() => Auth::user()->business_id !== 1),

                        TextInput::make('name')
                            ->label('Service Point Name')
                            ->placeholder('Enter service point name')
                            ->required(),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter service point description')
                            ->nullable(),
                    ])
                    ->successNotificationTitle('Service Point updated successfully.'),

                DeleteAction::make()
                    ->modalHeading('Delete Service Point')
                    ->successNotificationTitle('Service Point deleted (soft) successfully.'),
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
                    ->label('Create Service Point')
                    ->modalHeading('Add New Service Point')
                    ->form([
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->placeholder('Select a business')
                            ->options(Business::pluck('name', 'id'))
                            ->required()
                            ->default(Auth::user()->business_id)
                            ->disabled(fn() => Auth::user()->business_id !== 1),

                        TextInput::make('name')
                            ->label('Service Point Name')
                            ->placeholder('Enter service point name')
                            ->required(),

                        Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter service point description')
                            ->nullable(),
                    ])
                    ->createAnother(false)
                    ->after(function (ServicePoint $record) {
                        Notification::make()
                            ->title('Service Point created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.service_points.list-service-points');
    }
}
