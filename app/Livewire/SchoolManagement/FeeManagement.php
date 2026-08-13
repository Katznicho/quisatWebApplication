<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Fee;
use App\Models\Student;
use App\Services\FeeInvoiceService;
use App\Services\FeeParentNotificationService;
use App\Support\TenantScope;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class FeeManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    private function businessId(): ?int
    {
        return TenantScope::businessId();
    }

    private function studentOptions(): array
    {
        $businessId = $this->businessId();

        return Student::query()
            ->when($businessId, fn (Builder $q) => $q->where('business_id', $businessId))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Student $student) => [
                $student->id => trim("{$student->first_name} {$student->last_name} ({$student->student_id})"),
            ])
            ->all();
    }

    private function feeFormSchema(): array
    {
        return [
            Select::make('student_id')
                ->label('Student')
                ->options(fn () => $this->studentOptions())
                ->searchable()
                ->required(),
            TextInput::make('term_label')
                ->label('Term')
                ->maxLength(255)
                ->placeholder('e.g. Term 1 2026')
                ->helperText('Type the term or billing period. This is not a dropdown.'),
            TextInput::make('fee_type')
                ->label('Fee / bill type')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. Tuition, Uniform, Exam fee')
                ->datalist([
                    'Tuition',
                    'Library',
                    'Transport',
                    'Laboratory',
                    'Sports',
                    'Uniform',
                    'Exam fee',
                    'Development fee',
                    'Other',
                ]),
            TextInput::make('amount')
                ->numeric()
                ->required()
                ->minValue(0)
                ->placeholder('Enter amount'),
            DatePicker::make('due_date')
                ->required(),
            Textarea::make('notes')
                ->placeholder('Enter notes')
                ->rows(3),
            Select::make('external_payment_system')
                ->label('Other payment system')
                ->options([
                    'school_pay' => 'School Pay',
                    'sure_pay' => 'Sure Pay',
                    'other' => 'Other',
                ])
                ->placeholder('None (MarzPay / cash in app)')
                ->nullable(),
            TextInput::make('external_student_code')
                ->label('External student code / ID')
                ->maxLength(255)
                ->placeholder('School Pay / Sure Pay student code')
                ->visible(fn ($get) => filled($get('external_payment_system'))),
        ];
    }

    private function normalizeShared(array $data): array
    {
        $data['fee_type'] = trim((string) ($data['fee_type'] ?? ''));
        $data['term_label'] = trim((string) ($data['term_label'] ?? '')) ?: null;
        $data['external_payment_system'] = $data['external_payment_system'] ?: null;
        $data['external_student_code'] = trim((string) ($data['external_student_code'] ?? '')) ?: null;
        unset($data['term_id'], $data['payment_status'], $data['payment_date'], $data['receipt_number'], $data['payment_method']);

        if (! TenantScope::isSuperAdmin()) {
            $data['business_id'] = TenantScope::businessId();
        } elseif (empty($data['business_id']) && ! empty($data['student_id'])) {
            $student = Student::find($data['student_id']);
            $data['business_id'] = $student?->business_id;
        }

        return $data;
    }

    private function normalizeCreateData(array $data): array
    {
        $data = $this->normalizeShared($data);
        $amount = (float) ($data['amount'] ?? 0);
        $data['amount_paid'] = 0;
        $data['balance'] = $amount;
        $data['payment_status'] = 'pending';
        $data['payment_date'] = null;
        $data['receipt_number'] = null;
        $data['payment_method'] = null;

        return $data;
    }

    private function normalizeEditData(array $data, Fee $record): array
    {
        $data = $this->normalizeShared($data);
        $amount = (float) ($data['amount'] ?? $record->amount);
        $data['balance'] = max($amount - (float) $record->amount_paid, 0);

        return $data;
    }

    public function stats(): array
    {
        $query = Fee::query();
        TenantScope::apply($query);
        $fees = $query->get();

        return [
            'billed' => (float) $fees->sum('amount'),
            'paid' => (float) $fees->sum('amount_paid'),
            'pending' => (float) $fees->sum(fn (Fee $fee) => max($fee->remainingBalance(), 0)),
            'arrears' => (float) $fees->sum(fn (Fee $fee) => $fee->arrears()),
            'credits' => (float) $fees->sum(fn (Fee $fee) => $fee->credit()),
        ];
    }

    public function table(Table $table): Table
    {
        $query = Fee::query()->with(['student.parentGuardian', 'term', 'payments']);
        TenantScope::apply($query);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($state, Fee $record) => trim(
                        ($record->student?->first_name ?? '').' '.($record->student?->last_name ?? '')
                    ))
                    ->searchable(['students.first_name', 'students.last_name', 'students.student_id'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.parentGuardian.first_name')
                    ->label('Parent')
                    ->formatStateUsing(fn ($state, Fee $record) => $record->student?->parentGuardian?->full_name)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('term_label')
                    ->label('Term')
                    ->formatStateUsing(fn ($state, Fee $record) => $record->displayTerm() ?: '—')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fee_type')
                    ->label('Type')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('UGX')
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('UGX')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->money('UGX')
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Paid on')
                    ->date()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'secondary' => 'waived',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('external_student_code')
                    ->label('External ID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('payment_status')
                    ->options([
                        'pending' => 'Pending',
                        'partial' => 'Partial',
                        'overdue' => 'Overdue / arrears',
                        'paid' => 'Paid',
                        'waived' => 'Waived',
                    ]),
                SelectFilter::make('payment_method')
                    ->options([
                        'mobile_money' => 'MarzPay mobile money',
                        'card' => 'MarzPay card',
                        'cash' => 'Cash',
                        'other' => 'Other / attached proof',
                    ]),
                SelectFilter::make('external_payment_system')
                    ->label('External system')
                    ->options([
                        'school_pay' => 'School Pay',
                        'sure_pay' => 'Sure Pay',
                        'other' => 'Other',
                    ]),
                TrashedFilter::make(),
            ])
            ->actions([
                Action::make('view_proof')
                    ->label('Receipts')
                    ->icon('heroicon-o-photo')
                    ->visible(fn (Fee $record) => $record->payments->contains(fn ($p) => filled($p->proof_url) || filled($p->receipt_number)))
                    ->modalHeading('Payments & attached receipts')
                    ->modalContent(fn (Fee $record): View => view('fees.payment-proofs', ['fee' => $record->load('payments')]))
                    ->modalSubmitAction(false),
                EditAction::make()
                    ->modalHeading('Edit Fee')
                    ->form($this->feeFormSchema())
                    ->mutateFormDataUsing(fn (array $data, Fee $record): array => $this->normalizeEditData($data, $record))
                    ->successNotificationTitle('Fee updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete Fee')
                    ->successNotificationTitle('Fee deleted successfully (soft).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Fee')
                    ->modalHeading('Add New Fee')
                    ->form($this->feeFormSchema())
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizeCreateData($data))
                    ->createAnother(false)
                    ->after(function (Fee $record) {
                        try {
                            app(FeeInvoiceService::class)->generate($record);
                        } catch (\Throwable $e) {
                            report($e);
                        }

                        try {
                            app(FeeParentNotificationService::class)->notifyCreated($record);
                        } catch (\Throwable $e) {
                            report($e);
                        }

                        Notification::make()
                            ->title('Fee created successfully.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\ActionGroup::make([
                    Action::make('csv_payments')
                        ->label('All payments · CSV')
                        ->url(fn () => route('school-management.fees.report.csv', ['type' => 'payments']))
                        ->openUrlInNewTab(),
                    Action::make('pdf_payments')
                        ->label('All payments · PDF')
                        ->url(fn () => route('school-management.fees.report.pdf', ['type' => 'payments']))
                        ->openUrlInNewTab(),
                    Action::make('csv_pending')
                        ->label('Pending / arrears · CSV')
                        ->url(fn () => route('school-management.fees.report.csv', ['type' => 'pending']))
                        ->openUrlInNewTab(),
                    Action::make('pdf_pending')
                        ->label('Pending / arrears · PDF')
                        ->url(fn () => route('school-management.fees.report.pdf', ['type' => 'pending']))
                        ->openUrlInNewTab(),
                    Action::make('csv_methods')
                        ->label('By method · CSV')
                        ->url(fn () => route('school-management.fees.report.csv', ['type' => 'methods']))
                        ->openUrlInNewTab(),
                    Action::make('pdf_methods')
                        ->label('By method · PDF')
                        ->url(fn () => route('school-management.fees.report.pdf', ['type' => 'methods']))
                        ->openUrlInNewTab(),
                    Action::make('csv_recent')
                        ->label('Recent payments · CSV')
                        ->url(fn () => route('school-management.fees.report.csv', ['type' => 'recent']))
                        ->openUrlInNewTab(),
                    Action::make('pdf_recent')
                        ->label('Recent payments · PDF')
                        ->url(fn () => route('school-management.fees.report.pdf', ['type' => 'recent']))
                        ->openUrlInNewTab(),
                ])
                    ->label('Download / print reports')
                    ->icon('heroicon-o-document-arrow-down')
                    ->button(),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.fee-management', [
            'stats' => $this->stats(),
        ]);
    }
}
