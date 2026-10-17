<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ChurchVisitorRegistration;
use App\Models\ClassRoom;
use App\Services\ChurchVisitorService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class VisitorCheckInManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use \App\Livewire\Concerns\DownloadsTableDocuments;

    public string $reportDate = '';

    public function mount(): void
    {
        $business = auth()->user()?->business;
        abort_unless($business && $business->isChurch(), 403);
        $this->reportDate = Carbon::now($business->timezoneName())->toDateString();
    }

    public function table(Table $table): Table
    {
        $businessId = (int) auth()->user()->business_id;
        $service = app(ChurchVisitorService::class);

        $rows = ChurchVisitorRegistration::query()
            ->with(['visitor.family', 'visitor.registeredBy', 'attendance.checkedInBy', 'attendance.checkedOutBy', 'classRoom'])
            ->where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->whereHas('visitor', fn (Builder $query) => $query->whereNull('removed_at'))
            ->when($this->reportDate !== '', fn (Builder $query) => $query->whereDate('service_date', $this->reportDate));

        return $table
            ->query($this->orderByClassName($rows))
            ->filters([
                $this->classGroupFilter(),
            ])
            ->headerActions($this->tableDocumentActions('Visiting children'))
            ->columns([
                Tables\Columns\TextColumn::make('visitor.full_name')->label('Visiting child')->searchable(),
                Tables\Columns\TextColumn::make('visitor.relationship')->label('Relationship'),
                Tables\Columns\TextColumn::make('family')
                    ->label('Checked in with')
                    ->state(fn (ChurchVisitorRegistration $record) => $record->visitor?->family?->full_name ?: '—'),
                Tables\Columns\TextColumn::make('service_name')->label('Service')->placeholder('—'),
                Tables\Columns\TextColumn::make('classRoom.name')->label('Class')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state, ChurchVisitorRegistration $record) => $record->statusLabel()),
                Tables\Columns\TextColumn::make('registered')
                    ->label('Registered by')
                    ->state(fn (ChurchVisitorRegistration $record) => ($record->visitor?->registeredBy?->full_name ?: '—').' · Parent app'),
                Tables\Columns\TextColumn::make('arrival')
                    ->label('Arrival confirmed by')
                    ->state(fn (ChurchVisitorRegistration $record) => $record->attendance?->checkedInBy?->name ?: '—'),
            ])
            ->actions([
                Tables\Actions\Action::make('arrive')
                    ->label('Confirm arrival')
                    ->visible(fn (ChurchVisitorRegistration $record) => $record->status === 'awaiting')
                    ->form([
                        Select::make('class_room_id')
                            ->label('Class')
                            ->options(fn () => ClassRoom::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id'))
                            ->required(),
                    ])
                    ->action(function (ChurchVisitorRegistration $record, array $data) use ($service) {
                        try {
                            $service->arrive(auth()->user(), $record, (int) $data['class_room_id']);
                            Notification::make()->title('Arrival confirmed')->success()->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('checkout')
                    ->label('Check out')
                    ->visible(fn (ChurchVisitorRegistration $record) => $record->status === 'checked_in')
                    ->form([
                        TextInput::make('pickup_code')->label('Pickup code')->required()->maxLength(8),
                        TextInput::make('collector_name')->label('Collector')->maxLength(255),
                    ])
                    ->action(function (ChurchVisitorRegistration $record, array $data) use ($service) {
                        try {
                            $service->checkout(auth()->user(), $record, $data['pickup_code'], $data['collector_name'] ?? null);
                            Notification::make()->title('Visiting child checked out')->success()->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();
                        }
                    }),
            ]);
    }

    public function render()
    {
        $businessId = (int) auth()->user()->business_id;
        $rows = ChurchVisitorRegistration::query()
            ->where('business_id', $businessId)
            ->where('status', '!=', 'cancelled')
            ->when($this->reportDate !== '', fn ($query) => $query->whereDate('service_date', $this->reportDate))
            ->whereHas('visitor', fn ($query) => $query->whereNull('removed_at'))
            ->get(['status']);

        return view('livewire.school-management.visitor-check-in-management', [
            'awaiting' => $rows->where('status', 'awaiting')->count(),
            'inside' => $rows->where('status', 'checked_in')->count(),
            'checkedOut' => $rows->where('status', 'checked_out')->count(),
        ]);
    }
}
