<?php

namespace App\Livewire;

use App\Models\PortalUser;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListStaff extends Component implements HasActions, HasForms, HasTable
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
            ->query(PortalUser::query())
            ->columns([
                Tables\Columns\TextColumn::make('userName')
                    ->label('Username')
                    ->sortable()
                    ->searchable()
                    ->url(fn (PortalUser $record): string =>
                        route('staff.edit', ['staff' => $record->userName]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('fullName')
                    ->label('Full Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoneNo')
                    ->label('Phone Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('accountType')
                    ->label('Account Type')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([]);
    }

    public function render(): View
    {
        return view('livewire.list-staff');
    }
}

