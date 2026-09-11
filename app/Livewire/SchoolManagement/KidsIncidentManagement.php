<?php

namespace App\Livewire\SchoolManagement;

use App\Models\KidsIncident;
use App\Models\Student;
use App\Services\KidsChurchNotificationService;
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

class KidsIncidentManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $businessId = Auth::user()->business_id;

        return $table
            ->query(KidsIncident::query()->where('business_id', $businessId)->with('student'))
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')->label('Child')->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('title')->wrap(),
                Tables\Columns\TextColumn::make('notified_parent_at')->dateTime()->label('Parent notified'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Log incident / health report')
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data) use ($businessId) {
                        $data['business_id'] = $businessId;
                        $data['reported_by'] = Auth::id();

                        return $data;
                    })
                    ->after(function (KidsIncident $record) {
                        app(KidsChurchNotificationService::class)->notifyIncident($record);
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
            Select::make('student_id')
                ->label('Child')
                ->options(
                    Student::where('business_id', Auth::user()->business_id)
                        ->orderBy('first_name')
                        ->get()
                        ->mapWithKeys(fn (Student $student) => [$student->id => $student->full_name])
                )
                ->searchable()
                ->required(),
            Select::make('type')
                ->options([
                    'physical' => 'Physical',
                    'behavioral' => 'Behavioral',
                    'health' => 'Health',
                    'other' => 'Other',
                ])
                ->required(),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->required()->rows(4),
            Textarea::make('action_taken')->label('Action taken / care notes')->rows(3),
        ];
    }

    public function render(): View
    {
        return view('livewire.school-management.kids-incident-management');
    }
}
