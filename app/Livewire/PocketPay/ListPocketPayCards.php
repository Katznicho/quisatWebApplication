<?php

namespace App\Livewire\PocketPay;

use App\Models\Business;
use App\Models\PocketPayCard;
use App\Models\PocketPayCardNumber;
use App\Models\Student;
use App\Services\PocketPayService;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ListPocketPayCards extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        PocketPayAccess::authorize();
    }

    public function table(Table $table): Table
    {
        $canAssign = PocketPayAccess::canAssignCards();

        return $table
            ->query(TenantScope::apply(PocketPayCard::query())->with(['student.classRoom', 'business:id,name']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('card_number')->label('Card number')->searchable()->copyable()->weight('bold'),
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn (PocketPayCard $record): string => $record->student?->full_name ?? '—')
                    ->searchable(['first_name', 'last_name'])
                    ->description(fn (PocketPayCard $record): ?string => $record->student?->classRoom?->name),
                Tables\Columns\TextColumn::make('business.name')->label('School')->visible(TenantScope::isSuperAdmin())->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PocketPayCard::STATUS_ACTIVE => 'success',
                        PocketPayCard::STATUS_BLOCKED => 'danger',
                        default => 'warning',
                    })
                    ->description(fn (PocketPayCard $record): ?string => $record->isLocked() ? 'PIN locked' : null),
                Tables\Columns\TextColumn::make('balance')->label('Balance (UGX)')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('daily_limit')->label('Daily limit')->numeric()->placeholder('None'),
                Tables\Columns\TextColumn::make('activated_at')->label('Activated')->date('M d, Y')->placeholder('—')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    PocketPayCard::STATUS_INACTIVE => 'Not activated',
                    PocketPayCard::STATUS_ACTIVE => 'Active',
                    PocketPayCard::STATUS_BLOCKED => 'Blocked',
                ]),
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('School')
                    ->options(fn (): array => $this->schoolOptions())
                    ->searchable()
                    ->visible(TenantScope::isSuperAdmin()),
            ])
            ->headerActions([
                Action::make('assign')
                    ->label('Assign card')
                    ->icon('heroicon-o-credit-card')
                    ->visible($canAssign)
                    ->modalDescription('Link a card number from Card numbers to a student. The student or a parent then sets a 4-digit PIN to activate it.')
                    ->form([
                        Forms\Components\Select::make('business_id')
                            ->label('School')
                            ->options(fn (): array => $this->schoolOptions())
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('student_id', null)),
                        Forms\Components\Select::make('student_id')
                            ->label('Student')
                            ->searchable()
                            ->required()
                            ->disabled(fn (Get $get): bool => blank($get('business_id')))
                            ->getSearchResultsUsing(fn (string $search, Get $get): array => $this->studentsWithoutCard((int) $get('business_id'), $search))
                            ->options(fn (Get $get): array => $this->studentsWithoutCard((int) $get('business_id')))
                            ->helperText('Only students without a card are listed.'),
                        $this->cardNumberField(),
                    ])
                    ->action(function (array $data, Action $action): void {
                        abort_unless(PocketPayAccess::canAssignCards(), 403);

                        $student = Student::query()
                            ->where('business_id', $data['business_id'])
                            ->whereKey($data['student_id'])
                            ->doesntHave('pocketPayCard')
                            ->first();

                        if (! $student) {
                            Notification::make()->title('That student already has a card or is not in this school.')->danger()->send();
                            $action->halt();
                        }

                        $card = $this->useNumber((int) $data['card_number_id'], fn (PocketPayCardNumber $number) => PocketPayCard::create([
                            'business_id' => $student->business_id,
                            'student_id' => $student->id,
                            'card_number' => $number->number,
                            'status' => PocketPayCard::STATUS_INACTIVE,
                            'assigned_by' => Auth::id(),
                        ]));

                        if (! $card) {
                            Notification::make()->title('That card number is no longer available. Pick another.')->danger()->send();
                            $action->halt();
                        }

                        Notification::make()->title('Card assigned to '.$student->full_name)->success()->send();
                    }),
            ])
            ->actions([
                Action::make('statement')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->modalHeading(fn (PocketPayCard $record): string => 'Statement: '.($record->student?->full_name ?? $record->card_number))
                    ->modalContent(fn (PocketPayCard $record): View => view('pocket-pay.partials.card-statement', [
                        'card' => $record,
                        'transactions' => $record->transactions()->with('canteen:id,name')->latest('id')->limit(50)->get(),
                        'spentToday' => app(PocketPayService::class)->spentToday($record),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('change_number')
                    ->label('Change number')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('info')
                    ->visible($canAssign)
                    ->modalDescription('Use this when a card is lost or replaced. The balance, PIN and history stay with the student.')
                    ->form([$this->cardNumberField()])
                    ->action(function (PocketPayCard $record, array $data, Action $action): void {
                        abort_unless(PocketPayAccess::canAssignCards(), 403);

                        $card = $this->useNumber((int) $data['card_number_id'], function (PocketPayCardNumber $number) use ($record) {
                            PocketPayCardNumber::query()
                                ->where('pocket_pay_card_id', $record->id)
                                ->where('status', PocketPayCardNumber::STATUS_ASSIGNED)
                                ->update(['status' => PocketPayCardNumber::STATUS_RETIRED]);

                            $record->update(['card_number' => $number->number]);

                            return $record;
                        });

                        if (! $card) {
                            Notification::make()->title('That card number is no longer available. Pick another.')->danger()->send();
                            $action->halt();
                        }

                        Notification::make()->title('Card number updated')->success()->send();
                    }),
                Action::make('block')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (PocketPayCard $record): bool => $record->status !== PocketPayCard::STATUS_BLOCKED)
                    ->requiresConfirmation()
                    ->modalDescription('A blocked card cannot buy or be topped up until it is unblocked.')
                    ->action(function (PocketPayCard $record): void {
                        app(PocketPayService::class)->setBlocked($record, true, Auth::user());
                        Notification::make()->title('Card blocked')->success()->send();
                    }),
                Action::make('unblock')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (PocketPayCard $record): bool => $record->status === PocketPayCard::STATUS_BLOCKED)
                    ->requiresConfirmation()
                    ->action(function (PocketPayCard $record): void {
                        app(PocketPayService::class)->setBlocked($record, false, Auth::user());
                        Notification::make()->title('Card unblocked')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No cards assigned yet')
            ->emptyStateDescription($canAssign ? 'Generate numbers under Card numbers, then use "Assign card" to link them to students.' : 'Quisat assigns card numbers to your students.');
    }

    protected function cardNumberField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('card_number_id')
            ->label('Card number')
            ->searchable()
            ->required()
            ->getSearchResultsUsing(fn (string $search): array => $this->availableNumbers($search))
            ->options(fn (): array => $this->availableNumbers())
            ->helperText('Only unused numbers from Card numbers are listed. Search by any part of the number.');
    }

    protected function availableNumbers(?string $search = null): array
    {
        return PocketPayCardNumber::query()
            ->where('status', PocketPayCardNumber::STATUS_AVAILABLE)
            ->when($search, fn ($q) => $q->where('number', 'like', '%'.PocketPayCard::normalizeNumber($search).'%'))
            ->orderBy('number')
            ->limit(50)
            ->pluck('number', 'id')
            ->map(fn (string $number): string => PocketPayCardNumber::format($number))
            ->all();
    }

    /**
     * Claim an available number for a card, or return null when someone else just used it.
     */
    protected function useNumber(int $numberId, callable $apply): ?PocketPayCard
    {
        return DB::transaction(function () use ($numberId, $apply) {
            $number = PocketPayCardNumber::query()
                ->whereKey($numberId)
                ->where('status', PocketPayCardNumber::STATUS_AVAILABLE)
                ->lockForUpdate()
                ->first();

            if (! $number) {
                return null;
            }

            $card = $apply($number);

            $number->update([
                'status' => PocketPayCardNumber::STATUS_ASSIGNED,
                'pocket_pay_card_id' => $card->id,
                'assigned_at' => now(),
            ]);

            return $card;
        });
    }

    protected function studentsWithoutCard(int $businessId, ?string $search = null): array
    {
        if ($businessId < 1) {
            return [];
        }

        return Student::query()
            ->where('business_id', $businessId)
            ->doesntHave('pocketPayCard')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('student_id', 'like', "%{$search}%")))
            ->orderBy('first_name')
            ->limit(50)
            ->get(['id', 'first_name', 'last_name', 'student_id'])
            ->mapWithKeys(fn (Student $s) => [$s->id => trim($s->full_name.($s->student_id ? " ({$s->student_id})" : ''))])
            ->all();
    }

    protected function schoolOptions(): array
    {
        $service = app(PocketPayService::class);

        return Business::query()
            ->where('id', '!=', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'enabled_feature_ids'])
            ->filter(fn (Business $business) => $service->isEnabledFor($business))
            ->pluck('name', 'id')
            ->all();
    }

    public function render(): View
    {
        return view('livewire.pocket-pay.table');
    }
}
