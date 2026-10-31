<?php

namespace App\Livewire\PocketPay;

use App\Models\PocketPayCard;
use App\Models\PocketPayCardNumber;
use App\Support\PocketPayAccess;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ListPocketPayCardNumbers extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        abort_unless(PocketPayAccess::canAssignCards(), 403);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PocketPayCardNumber::query()->with(['card.student', 'card.business:id,name']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->label('Card number')
                    ->formatStateUsing(fn (string $state): string => PocketPayCardNumber::format($state))
                    ->searchable()
                    ->copyable()
                    ->copyableState(fn (PocketPayCardNumber $record): string => $record->number)
                    ->fontFamily('mono')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        PocketPayCardNumber::STATUS_AVAILABLE => 'success',
                        PocketPayCardNumber::STATUS_ASSIGNED => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('card.student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn (PocketPayCardNumber $record): string => $record->card?->student?->full_name ?? '—')
                    ->description(fn (PocketPayCardNumber $record): ?string => $record->card?->business?->name)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('batch')->searchable()->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Created')->date('M d, Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    PocketPayCardNumber::STATUS_AVAILABLE => 'Available',
                    PocketPayCardNumber::STATUS_ASSIGNED => 'Assigned',
                    PocketPayCardNumber::STATUS_RETIRED => 'Retired',
                ]),
                Tables\Filters\SelectFilter::make('batch')
                    ->options(fn (): array => PocketPayCardNumber::query()->whereNotNull('batch')->distinct()->orderBy('batch')->pluck('batch', 'batch')->all())
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('generate')
                    ->label('Generate numbers')
                    ->icon('heroicon-o-sparkles')
                    ->modalDescription('Creates unique 12-digit numbers starting with the year, e.g. 2604 1234 4821. The last digit is a check digit, so typos are caught.')
                    ->form([
                        Forms\Components\TextInput::make('count')
                            ->label('How many')
                            ->numeric()
                            ->integer()
                            ->required()
                            ->minValue(1)
                            ->maxValue(PocketPayCardNumber::MAX_BATCH)
                            ->default(50),
                        Forms\Components\TextInput::make('batch')
                            ->label('Batch label')
                            ->maxLength(50)
                            ->placeholder('e.g. Print run Nov 2026'),
                    ])
                    ->action(function (array $data): void {
                        abort_unless(PocketPayAccess::canAssignCards(), 403);

                        $created = PocketPayCardNumber::generate((int) $data['count'], $data['batch'] ?: null, Auth::id());

                        Notification::make()->title("{$created->count()} card numbers generated")->success()->send();
                    }),
                Action::make('add')
                    ->label('Add number')
                    ->icon('heroicon-o-plus')
                    ->color('gray')
                    ->modalDescription('Add a number that is already printed on a physical card.')
                    ->form([
                        Forms\Components\TextInput::make('number')
                            ->label('Card number')
                            ->required()
                            ->maxLength(32)
                            ->rules([
                                'regex:/^[A-Za-z0-9\s-]{4,32}$/',
                                fn () => function (string $attribute, $value, \Closure $fail) {
                                    if (PocketPayCardNumber::isTaken($value)) {
                                        $fail('This card number already exists.');
                                    }
                                },
                            ])
                            ->helperText('Letters and digits; spaces and dashes are ignored.'),
                        Forms\Components\TextInput::make('batch')->label('Batch label')->maxLength(50),
                    ])
                    ->action(function (array $data): void {
                        abort_unless(PocketPayAccess::canAssignCards(), 403);

                        PocketPayCardNumber::create([
                            'number' => $data['number'],
                            'batch' => $data['batch'] ?: null,
                            'status' => PocketPayCardNumber::STATUS_AVAILABLE,
                            'created_by' => Auth::id(),
                        ]);

                        Notification::make()->title('Card number added')->success()->send();
                    }),
            ])
            ->actions([
                Action::make('delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (PocketPayCardNumber $record): bool => $record->status === PocketPayCardNumber::STATUS_AVAILABLE)
                    ->requiresConfirmation()
                    ->modalDescription('Only unused numbers can be deleted.')
                    ->action(function (PocketPayCardNumber $record): void {
                        abort_unless(PocketPayAccess::canAssignCards(), 403);

                        if ($record->status === PocketPayCardNumber::STATUS_AVAILABLE) {
                            $record->delete();
                        }

                        Notification::make()->title('Card number deleted')->success()->send();
                    }),
            ])
            ->emptyStateHeading('No card numbers yet')
            ->emptyStateDescription('Use "Generate numbers" to create a batch, then assign them to students from Cards.');
    }

    public function render(): View
    {
        return view('livewire.pocket-pay.table');
    }
}
