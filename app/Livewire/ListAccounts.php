<?php

namespace App\Livewire;

use App\Models\Account;
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

class ListAccounts extends Component implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public ?string $status = null;

    public function mount(?string $status = null): void
    {
        $this->status = $status;
    }

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        $query = Account::query();

        if ($this->status === 'active') {
            $query->where('Blocked', false);
        } elseif ($this->status === 'blocked') {
            $query->where('Blocked', true);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('accountNo')
                    ->label('Account No')
                    ->sortable()
                    ->searchable()
                    ->url(fn (Account $record): string =>
                        route('accounts.edit', ['account' => $record->accountNo]))
                    ->color('primary'),
                Tables\Columns\TextColumn::make('cardNo')
                    ->label('Card Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('accountName')
                    ->label('Account Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->placeholder('—')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phoneNo')
                    ->label('Phone Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicleRegNo')
                    ->label('Vehicle Reg No')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([]);
    }

    public function render(): View
    {
        return view('livewire.list-accounts');
    }
}

