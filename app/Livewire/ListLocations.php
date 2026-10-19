<?php

namespace App\Livewire;

use App\Models\Location;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

use Filament\Support\Contracts\TranslatableContentDriver;

class ListLocations extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Location::query())
            ->columns([
                Tables\Columns\TextColumn::make('Code')
                    ->label('Station Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('Name')
                    ->label('Station Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('district')
                    ->label('District')
                    ->placeholder('—')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('LocationType')
                    ->label('Type')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoneNumber')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([]);
    }

    public function render(): View
    {
        return view('livewire.list-locations');
    }
}

