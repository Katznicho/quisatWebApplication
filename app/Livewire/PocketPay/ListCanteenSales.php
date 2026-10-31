<?php

namespace App\Livewire\PocketPay;

use App\Exceptions\PocketPayException;
use App\Models\Canteen;
use App\Models\CanteenSale;
use App\Services\PocketPayService;
use App\Support\PocketPayAccess;
use App\Support\TenantScope;
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

/**
 * Canteen sales, and the reversal approval queue (reversalsOnly).
 */
class ListCanteenSales extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public ?Canteen $canteen = null;

    public bool $reversalsOnly = false;

    public function mount(?Canteen $canteen = null, bool $reversalsOnly = false): void
    {
        PocketPayAccess::authorize();

        if ($canteen?->exists) {
            TenantScope::authorizeModel($canteen);
            $this->canteen = $canteen;
        }

        $this->reversalsOnly = $reversalsOnly;
    }

    public function table(Table $table): Table
    {
        $query = TenantScope::apply(CanteenSale::query())
            ->with(['student:id,first_name,last_name', 'card:id,card_number', 'canteen:id,name', 'attendant:id,name', 'reviewer:id,name'])
            ->when($this->canteen, fn ($q) => $q->where('canteen_id', $this->canteen->id))
            ->when($this->reversalsOnly, fn ($q) => $q->whereNotNull('reversal_status'));

        return $table
            ->query($query)
            ->heading($this->reversalsOnly ? null : 'Sales')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('M d, Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('receipt_number')->label('Receipt')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn (CanteenSale $record): string => $record->student?->full_name ?? '—'),
                Tables\Columns\TextColumn::make('canteen.name')->label('Canteen')->visible(! $this->canteen),
                Tables\Columns\TextColumn::make('attendant.name')->label('Attendant')->placeholder('—'),
                Tables\Columns\TextColumn::make('amount')->label('Amount (UGX)')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('description')->placeholder('—')->limit(30)->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => $state === CanteenSale::STATUS_REVERSED ? 'gray' : 'success'),
                Tables\Columns\TextColumn::make('reversal_status')
                    ->label('Reversal')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->color(fn (?string $state): string => match ($state) {
                        CanteenSale::REVERSAL_PENDING => 'warning',
                        CanteenSale::REVERSAL_APPROVED => 'success',
                        CanteenSale::REVERSAL_REJECTED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('reversal_reason')->label('Reason')->wrap()->placeholder('—')->visible($this->reversalsOnly),
                Tables\Columns\TextColumn::make('reviewer.name')->label('Reviewed by')->placeholder('—')->toggleable(isToggledHiddenByDefault: ! $this->reversalsOnly),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('reversal_status')
                    ->label('Reversal')
                    ->options([
                        CanteenSale::REVERSAL_PENDING => 'Awaiting approval',
                        CanteenSale::REVERSAL_APPROVED => 'Approved',
                        CanteenSale::REVERSAL_REJECTED => 'Rejected',
                    ])
                    ->default($this->reversalsOnly ? CanteenSale::REVERSAL_PENDING : null),
                Tables\Filters\SelectFilter::make('canteen_id')
                    ->label('Canteen')
                    ->options(fn (): array => TenantScope::apply(Canteen::query())->orderBy('name')->pluck('name', 'id')->all())
                    ->visible(! $this->canteen),
            ])
            ->actions([
                Action::make('approve')
                    ->label('Approve reversal')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (CanteenSale $record): bool => $record->hasPendingReversal() && PocketPayAccess::canReviewReversals())
                    ->modalHeading(fn (CanteenSale $record): string => 'Approve reversal of '.$record->receipt_number)
                    ->modalDescription(fn (CanteenSale $record): string => 'UGX '.number_format($record->amount).' goes back to the student card and to the canteen float. Reason given: "'.$record->reversal_reason.'"')
                    ->form([Forms\Components\Textarea::make('note')->label('Note (optional)')->maxLength(255)->placeholder('e.g. Confirmed with the student, wrong amount keyed')])
                    ->action(function (CanteenSale $record, array $data): void {
                        abort_unless(PocketPayAccess::canReviewReversals(), 403);
                        $this->review(fn () => app(PocketPayService::class)->approveReversal($record, Auth::user(), $data['note'] ?? null), 'Reversal approved. Card and float refunded.');
                    }),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (CanteenSale $record): bool => $record->hasPendingReversal() && PocketPayAccess::canReviewReversals())
                    ->modalHeading(fn (CanteenSale $record): string => 'Reject reversal of '.$record->receipt_number)
                    ->form([Forms\Components\Textarea::make('note')->label('Why is it rejected?')->required()->maxLength(255)->placeholder('e.g. The student received the item')])
                    ->action(function (CanteenSale $record, array $data): void {
                        abort_unless(PocketPayAccess::canReviewReversals(), 403);
                        $this->review(fn () => app(PocketPayService::class)->rejectReversal($record, Auth::user(), $data['note']), 'Reversal rejected. The sale stands.');
                    }),
            ])
            ->emptyStateHeading($this->reversalsOnly ? 'No reversal requests' : 'No sales yet');
    }

    protected function review(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->title($success)->success()->send();
        } catch (PocketPayException $e) {
            Notification::make()->title($e->getMessage())->danger()->send();
        }
    }

    public function render(): View
    {
        return view('livewire.pocket-pay.table');
    }
}
