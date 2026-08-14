<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicPatient;
use App\Models\ClinicService;
use App\Models\Fee;
use App\Services\FeeInvoiceService;
use App\Services\FeeParentNotificationService;
use App\Support\TenantScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
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
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class FeeManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public ?ClinicPatient $patient = null;

    private function businessId(): ?int
    {
        return TenantScope::businessId() ?? auth()->user()?->business_id;
    }

    private function patientOptions(): array
    {
        $businessId = $this->businessId();

        return ClinicPatient::query()
            ->when($businessId, fn (Builder $q) => $q->where('business_id', $businessId))
            ->when($this->patient, fn (Builder $q) => $q->whereKey($this->patient->id))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (ClinicPatient $patient) => [
                $patient->id => trim("{$patient->first_name} {$patient->last_name} ({$patient->patient_number})"),
            ])
            ->all();
    }

    private function serviceFeeTypeSuggestions(): array
    {
        $defaults = [
            'Consultation',
            'Lab test',
            'Medication',
            'Vaccination',
            'Procedure',
            'Follow-up',
            'Other',
        ];

        if (! Schema::hasTable('clinic_services')) {
            return $defaults;
        }

        $businessId = $this->businessId();
        $serviceNames = ClinicService::query()
            ->when($businessId, fn (Builder $q) => $q->where('business_id', $businessId))
            ->orderBy('name')
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($serviceNames, $defaults)));
    }

    private function feeFormSchema(): array
    {
        $schema = [];

        if ($this->patient) {
            $schema[] = Hidden::make('clinic_patient_id')->default($this->patient->id);
        } else {
            $schema[] = Select::make('clinic_patient_id')
                ->label('Patient')
                ->options(fn () => $this->patientOptions())
                ->searchable()
                ->required()
                ->helperText('Choose the clinic patient to bill.');
        }

        $schema = array_merge($schema, [
            TextInput::make('term_label')
                ->label('Billing period')
                ->maxLength(255)
                ->placeholder('e.g. August 2026 visit')
                ->helperText('Optional label for the bill (visit date, month, package, etc.).'),
            TextInput::make('fee_type')
                ->label('Fee / bill type')
                ->required()
                ->maxLength(255)
                ->placeholder('e.g. Consultation, Lab test')
                ->datalist($this->serviceFeeTypeSuggestions()),
            TextInput::make('amount')
                ->numeric()
                ->required()
                ->minValue(0)
                ->placeholder('Enter amount'),
            DatePicker::make('due_date')
                ->required()
                ->default(now()),
            Textarea::make('notes')
                ->placeholder('Enter notes')
                ->rows(3),
        ]);

        return $schema;
    }

    private function normalizeShared(array $data): array
    {
        $data['fee_type'] = trim((string) ($data['fee_type'] ?? ''));
        $data['term_label'] = trim((string) ($data['term_label'] ?? '')) ?: null;
        $data['student_id'] = null;
        $data['external_payment_system'] = null;
        $data['external_student_code'] = null;
        unset($data['term_id'], $data['payment_status'], $data['payment_date'], $data['receipt_number'], $data['payment_method']);

        if ($this->patient) {
            $data['clinic_patient_id'] = $this->patient->id;
        }

        if (! TenantScope::isSuperAdmin()) {
            $data['business_id'] = $this->businessId();
        } elseif (empty($data['business_id']) && ! empty($data['clinic_patient_id'])) {
            $patient = ClinicPatient::find($data['clinic_patient_id']);
            $data['business_id'] = $patient?->business_id;
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
        $query = Fee::query()->whereNotNull('clinic_patient_id');
        $this->scopeClinicFees($query);
        $fees = $query->get();

        return [
            'billed' => (float) $fees->sum('amount'),
            'paid' => (float) $fees->sum('amount_paid'),
            'pending' => (float) $fees->sum(fn (Fee $fee) => max($fee->remainingBalance(), 0)),
            'arrears' => (float) $fees->sum(fn (Fee $fee) => $fee->arrears()),
            'credits' => (float) $fees->sum(fn (Fee $fee) => $fee->credit()),
        ];
    }

    private function scopeClinicFees(Builder $query): void
    {
        $businessId = $this->businessId();
        if ($businessId && ! TenantScope::isSuperAdmin()) {
            $query->where('business_id', $businessId);
        }

        if ($this->patient) {
            $query->where('clinic_patient_id', $this->patient->id);
        }
    }

    public function table(Table $table): Table
    {
        $query = Fee::query()
            ->with(['clinicPatient.parentGuardian', 'term', 'payments'])
            ->whereNotNull('clinic_patient_id');
        $this->scopeClinicFees($query);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('clinicPatient.first_name')
                    ->label('Patient')
                    ->formatStateUsing(fn ($state, Fee $record) => $record->clinicPatient?->full_name)
                    ->searchable(['clinic_patients.first_name', 'clinic_patients.last_name', 'clinic_patients.patient_number'])
                    ->sortable()
                    ->visible(fn () => ! $this->patient),
                Tables\Columns\TextColumn::make('clinicPatient.parentGuardian.first_name')
                    ->label('Parent')
                    ->formatStateUsing(fn ($state, Fee $record) => $record->clinicPatient?->parentGuardian?->full_name)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('term_label')
                    ->label('Period')
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
                    ->modalHeading('Edit clinic fee')
                    ->form($this->feeFormSchema())
                    ->mutateFormDataUsing(fn (array $data, Fee $record): array => $this->normalizeEditData($data, $record))
                    ->successNotificationTitle('Clinic fee updated successfully.'),
                DeleteAction::make()
                    ->modalHeading('Delete clinic fee')
                    ->successNotificationTitle('Clinic fee deleted successfully (soft).'),
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
                    ->label('Bill patient')
                    ->modalHeading($this->patient ? 'Bill '.$this->patient->full_name : 'Bill clinic patient')
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
                            ->title('Clinic fee created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.fee-management', [
            'stats' => $this->stats(),
            'scopedPatient' => $this->patient,
        ]);
    }
}
