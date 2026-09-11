<?php

namespace App\Livewire\SchoolManagement;

use App\Models\KidsVolunteer;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class KidsVolunteerManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $businessId = Auth::user()->business_id;

        return $table
            ->query(KidsVolunteer::query()->where('business_id', $businessId))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('background_check_status')
                    ->label('Background check')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('hours_served')->label('Hours'),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Add volunteer')
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data) use ($businessId) {
                        $data['business_id'] = $businessId;

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()->form($this->formSchema()),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function formSchema(): array
    {
        return [
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('phone')->maxLength(50),
            TextInput::make('email')->email()->maxLength(255),
            TextInput::make('role')->placeholder('Teacher, helper, security…')->maxLength(120),
            Select::make('user_id')
                ->label('Linked staff account (optional)')
                ->options(User::where('business_id', Auth::user()->business_id)->orderBy('name')->pluck('name', 'id'))
                ->searchable(),
            Select::make('background_check_status')
                ->options([
                    'pending' => 'Pending',
                    'cleared' => 'Cleared',
                    'expired' => 'Expired',
                ])
                ->default('pending')
                ->required(),
            TextInput::make('hours_served')->numeric()->default(0),
            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ])
                ->default('active')
                ->required(),
            Textarea::make('notes')->rows(3),
        ];
    }

    public function render(): View
    {
        return view('livewire.school-management.kids-volunteer-management');
    }
}
