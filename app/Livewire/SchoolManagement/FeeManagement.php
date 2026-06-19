<?php

namespace App\Livewire\SchoolManagement;

use App\Models\Fee;
use App\Models\Student;
use App\Models\Term;
use App\Support\TenantScope;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\TrashedFilter;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

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
                $student->id => trim("{$student->first_name} {$student->last_name}"),
            ])
            ->all();
    }

    private function termOptions(): array
    {
        $businessId = $this->businessId();

        return Term::query()
            ->when($businessId, fn (Builder $q) => $q->where('business_id', $businessId))
            ->orderByDesc('start_date')
            ->get()
            ->mapWithKeys(fn (Term $term) => [
                $term->id => trim("{$term->name} ({$term->academic_year})"),
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
            Select::make('term_id')
                ->label('Term')
                ->options(fn () => $this->termOptions())
                ->searchable()
                ->nullable(),
            Select::make('fee_type')
                ->label('Fee Type')
                ->options([
                    'tuition' => 'Tuition',
                    'library' => 'Library',
                    'transport' => 'Transport',
                    'laboratory' => 'Laboratory',
                    'sports' => 'Sports',
                    'other' => 'Other',
                ])
                ->required(),
            TextInput::make('amount')
                ->numeric()
                ->required()
                ->minValue(0)
                ->placeholder('Enter amount'),
            TextInput::make('amount_paid')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->placeholder('Enter amount paid'),
            DatePicker::make('due_date')
                ->required(),
            Select::make('payment_status')
                ->options([
                    'pending' => 'Pending',
                    'partial' => 'Partial',
                    'paid' => 'Paid',
                    'overdue' => 'Overdue',
                    'waived' => 'Waived',
                ])
                ->default('pending')
                ->required(),
            Select::make('payment_method')
                ->options([
                    'cash' => 'Cash',
                    'mobile_money' => 'Mobile Money',
                    'bank_transfer' => 'Bank Transfer',
                    'check' => 'Check',
                ])
                ->nullable(),
            DatePicker::make('payment_date')
                ->nullable(),
            TextInput::make('receipt_number')
                ->placeholder('Enter receipt number')
                ->maxLength(255),
            Textarea::make('notes')
                ->placeholder('Enter notes')
                ->rows(3),
        ];
    }

    private function normalizeFeeData(array $data): array
    {
        $amount = (float) ($data['amount'] ?? 0);
        $amountPaid = (float) ($data['amount_paid'] ?? 0);
        $data['amount_paid'] = $amountPaid;
        $data['balance'] = max($amount - $amountPaid, 0);

        if (! TenantScope::isSuperAdmin()) {
            $data['business_id'] = TenantScope::businessId();
        } elseif (empty($data['business_id']) && ! empty($data['student_id'])) {
            $student = Student::find($data['student_id']);
            $data['business_id'] = $student?->business_id;
        }

        return $data;
    }

    public function table(Table $table): Table
    {
        $query = Fee::query()->with(['student', 'term']);
        TenantScope::apply($query);

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('student.first_name')
                    ->label('Student')
                    ->formatStateUsing(fn ($state, Fee $record) => trim(
                        ($record->student?->first_name ?? '') . ' ' . ($record->student?->last_name ?? '')
                    ))
                    ->searchable(['students.first_name', 'students.last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('term.name')
                    ->label('Term')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('fee_type')
                    ->label('Type')
                    ->colors([
                        'primary' => 'tuition',
                        'success' => 'transport',
                        'warning' => 'library',
                        'danger' => 'laboratory',
                        'info' => 'sports',
                        'secondary' => 'other',
                    ]),
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
                Tables\Columns\BadgeColumn::make('payment_status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                        'secondary' => 'waived',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                EditAction::make()
                    ->modalHeading('Edit Fee')
                    ->form($this->feeFormSchema())
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizeFeeData($data))
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
                    ->mutateFormDataUsing(fn (array $data): array => $this->normalizeFeeData($data))
                    ->createAnother(false)
                    ->after(function (Fee $record) {
                        Notification::make()
                            ->title('Fee created successfully.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.school-management.fee-management');
    }
}
