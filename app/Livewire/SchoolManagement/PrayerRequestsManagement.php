<?php

namespace App\Livewire\SchoolManagement;

use App\Models\PrayerRequest;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PrayerRequestsManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PrayerRequest::query()
                    ->with(['student', 'parentGuardian'])
                    ->forBusiness((int) Auth::user()->business_id)
            )
            ->columns([
                Tables\Columns\TextColumn::make('body')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('parentGuardian.full_name')
                    ->label('Parent')
                    ->placeholder('—')
                    ->formatStateUsing(function (?string $state, PrayerRequest $record): string {
                        if ($record->is_anonymous) {
                            return 'Anonymous';
                        }

                        return $state ?: '—';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('parentGuardian', function ($parent) use ($search) {
                            $parent->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\IconColumn::make('is_anonymous')->boolean()->label('Anonymous'),
                Tables\Columns\TextColumn::make('student.full_name')->label('Child'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'being_prayed_for' => 'Being prayed for',
                        'answered' => 'Answered',
                        default => 'Received',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Update prayer request')
                    ->form([
                        Select::make('status')
                            ->options([
                                'received' => 'Received',
                                'being_prayed_for' => 'Being prayed for',
                                'answered' => 'Answered',
                            ])
                            ->required(),
                        Textarea::make('praise_report')
                            ->label('Praise report')
                            ->rows(3),
                    ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public function render(): View
    {
        return view('livewire.school-management.prayer-requests-management');
    }
}
