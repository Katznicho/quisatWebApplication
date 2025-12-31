<?php

namespace App\Livewire\Programs;

use App\Models\Program;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class ListPrograms extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(Program::query())
            ->recordUrl(fn (Program $record): string => route('programs.show', $record))
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Media')
                    ->disk('public')
                    ->height(50)
                    ->width(50)
                    ->defaultImageUrl(null),
                Tables\Columns\TextColumn::make('media_type')
                    ->label('Type')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->image) {
                            return 'Image';
                        } elseif ($record->video) {
                            return 'Video';
                        }
                        return '—';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Image' => 'success',
                        'Video' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'Image' => 'heroicon-o-photo',
                        'Video' => 'heroicon-o-video-camera',
                        default => '',
                    }),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('age-group')
                    ->label('Age Group')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_events')
                    ->label('Total Events')
                    ->counts('events')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (Program $record): string => route('programs.show', $record))
                    ->openUrlInNewTab(false),
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->url(fn (Program $record): string => route('programs.edit', $record))
                    ->openUrlInNewTab(false),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('Create Program')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->url(route('programs.create'))
                    ->openUrlInNewTab(false),
            ]);
    }

    public function render(): View
    {
        return view('livewire.programs.list-programs');
    }
}
