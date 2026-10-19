<?php

namespace App\Livewire;

use App\Models\Driver;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListDrivers extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Driver::query())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Driver Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Driver Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoneNo')
                    ->label('Phone Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('blocked')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Blocked' : 'Active')
                    ->sortable(),
                Tables\Columns\TextColumn::make('customerNo')
                    ->label('Customer No')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([]);
    }

    public function render(): View
    {
        return view('livewire.list-drivers');
    }
}

