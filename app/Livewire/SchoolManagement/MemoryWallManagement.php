<?php

namespace App\Livewire\SchoolManagement;

use App\Models\MemoryWallItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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

class MemoryWallManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        return $table
            ->query(MemoryWallItem::query()->where('business_id', Auth::user()->business_id))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'memory_verse' ? 'Memory verse' : 'Prayer focus'),
                Tables\Columns\TextColumn::make('title'),
                Tables\Columns\TextColumn::make('body')->limit(60),
                Tables\Columns\TextColumn::make('scripture_ref')->label('Reference'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('starts_on')->date(),
                Tables\Columns\TextColumn::make('ends_on')->date(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data) {
                        $data['business_id'] = Auth::user()->business_id;
                        $data['created_by'] = Auth::id();

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
            Select::make('type')
                ->options([
                    'memory_verse' => 'Memory verse',
                    'prayer_focus' => 'Prayer focus',
                ])
                ->required(),
            TextInput::make('title')->maxLength(255),
            Textarea::make('body')->required()->rows(4),
            TextInput::make('scripture_ref')->label('Scripture reference')->maxLength(120),
            DatePicker::make('starts_on'),
            DatePicker::make('ends_on'),
            Toggle::make('is_active')->default(true),
        ];
    }

    public function render(): View
    {
        return view('livewire.school-management.memory-wall-management');
    }
}
