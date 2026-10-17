<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ChurchService;
use App\Models\WalkinVisit;
use App\Services\WalkInVisitorService;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class WalkInVisitorManagement extends Component implements HasForms, HasTable
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
        $service = app(WalkInVisitorService::class);
        $canException = auth()->user()->isAdmin() || auth()->user()->isBusinessAdmin();

        $rows = WalkinVisit::query()
            ->with(['child.family', 'child.visits', 'classRoom', 'checkedInBy', 'checkedOutBy'])
            ->where('business_id', $businessId)
            ->when($this->reportDate !== '', fn (Builder $query) => $query->whereDate('service_date', $this->reportDate));

        return $table
            ->query($this->orderByClassName($rows))
            ->filters([
                $this->classGroupFilter(),
                SelectFilter::make('church_service_id')
                    ->label('Service')
                    ->options(fn () => ChurchService::query()->where('business_id', $businessId)->orderBy('name')->pluck('name', 'id')),
                SelectFilter::make('status')
                    ->options([
                        'checked_in' => 'Inside',
                        'checked_out' => 'Checked out',
                    ]),
            ])
            ->headerActions($this->tableDocumentActions('Walk-in visitors'))
            ->columns([
                Tables\Columns\TextColumn::make('child.full_name')->label('Child')->searchable(),
                Tables\Columns\TextColumn::make('age')->label('Age')->state(fn (WalkinVisit $record) => $record->child?->ageLabel() ?: '—'),
                Tables\Columns\TextColumn::make('classRoom.name')->label('Class')->placeholder('—'),
                Tables\Columns\TextColumn::make('child.family.guardian_name')->label('Guardian')->searchable(),
                Tables\Columns\TextColumn::make('child.family.phone')->label('Phone')->searchable(),
                Tables\Columns\TextColumn::make('checked_in_at')->label('Check-in')->dateTime('H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->formatStateUsing(fn (string $state, WalkinVisit $record) => $record->statusLabel()),
                Tables\Columns\TextColumn::make('visit_type')
                    ->label('Visit')
                    ->state(fn (WalkinVisit $record) => WalkinVisit::query()->where('walkin_child_id', $record->walkin_child_id)->where('id', '<', $record->id)->exists() ? 'Returning' : 'First time'),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->label('View details')
                    ->modalHeading('Visitor details')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (WalkinVisit $record) => view('livewire.school-management.walk-in-visitor-details', [
                        'visit' => $record->loadMissing('child.family', 'classRoom', 'checkedInBy', 'checkedOutBy'),
                        'siblings' => WalkinVisit::query()
                            ->with('child', 'classRoom')
                            ->where('business_id', $businessId)
                            ->where('walkin_family_id', $record->walkin_family_id)
                            ->whereDate('service_date', $record->service_date)
                            ->where('church_service_id', $record->church_service_id)
                            ->where('id', '!=', $record->id)
                            ->get(),
                    ])),
                Tables\Actions\Action::make('checkout')
                    ->label('Verify collection')
                    ->visible(fn (WalkinVisit $record) => $record->status === 'checked_in')
                    ->fillForm(fn (WalkinVisit $record): array => [
                        'collector_name' => $record->collector_name ?: $record->family?->collector_name,
                    ])
                    ->form([
                        TextInput::make('pickup_code')->label('Pickup code')->maxLength(8),
                        TextInput::make('collector_name')->label('Collector')->maxLength(120),
                        Checkbox::make('collector_confirmed')->label('I have confirmed this collector')->rule('accepted'),
                        Textarea::make('exception_reason')
                            ->label('Missing-code reason')
                            ->helperText($canException ? 'Church administrators only. Leave blank when the pickup code is entered.' : 'A pickup code is required.')
                            ->visible($canException)
                            ->maxLength(255),
                    ])
                    ->action(function (WalkinVisit $record, array $data) use ($service) {
                        try {
                            $service->checkout(auth()->user(), (int) $record->business_id, [
                                'visit_ids' => [$record->id],
                                'pickup_code' => $data['pickup_code'] ?? null,
                                'collector_name' => $data['collector_name'] ?? null,
                                'collector_confirmed' => (bool) ($data['collector_confirmed'] ?? false),
                                'exception_reason' => $data['exception_reason'] ?? null,
                            ]);
                            Notification::make()->title('Child checked out')->success()->send();
                        } catch (ValidationException $exception) {
                            Notification::make()->title(collect($exception->errors())->flatten()->first())->danger()->send();
                        }
                    }),
            ]);
    }

    public function render()
    {
        $businessId = (int) auth()->user()->business_id;
        $rows = WalkinVisit::query()
            ->where('business_id', $businessId)
            ->when($this->reportDate !== '', fn ($query) => $query->whereDate('service_date', $this->reportDate))
            ->get(['status']);

        return view('livewire.school-management.walk-in-visitor-management', [
            'inside' => $rows->where('status', 'checked_in')->count(),
            'checkedOut' => $rows->where('status', 'checked_out')->count(),
        ]);
    }
}
