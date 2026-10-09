<?php

namespace App\Livewire\SchoolManagement;

use App\Models\StaffAttendance;
use App\Services\StaffAttendanceService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;

class StaffAttendanceManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canViewStaffAttendance(), 403);
    }

    public function table(Table $table): Table
    {
        $actor = auth()->user();
        $businessId = $actor?->business_id;
        $service = app(StaffAttendanceService::class);

        return $table
            ->query($service->adminQuery($actor))
            ->defaultSort('checked_in_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Staff member')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('attendance_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('checked_in_at')
                    ->label('Arrival')
                    ->formatStateUsing(fn ($state, StaffAttendance $record) => $service->formatTime($record->checked_in_at, $record->business?->timezoneName() ?? config('app.timezone'))),
                Tables\Columns\TextColumn::make('checked_out_at')
                    ->label('Departure')
                    ->formatStateUsing(fn ($state, StaffAttendance $record) => $record->checked_out_at
                        ? $service->formatTime($record->checked_out_at, $record->business?->timezoneName() ?? config('app.timezone'))
                        : '—'),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->state(fn (StaffAttendance $record) => $service->totalLabel($record->checked_in_at, $record->checked_out_at) ?? '—'),
                Tables\Columns\TextColumn::make('recordedBy.name')
                    ->label('Recorded by')
                    ->placeholder('—'),
            ])
            ->filters([
                Filter::make('attendance_date')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('attendance_date', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('attendance_date', '<=', $date));
                    }),
                SelectFilter::make('user_id')
                    ->label('Staff member')
                    ->relationship('user', 'name', function (Builder $query) use ($businessId) {
                        if ($businessId) {
                            $query->where('business_id', $businessId);
                        }
                    }),
                SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'name', function (Builder $query) use ($businessId) {
                        if ($businessId) {
                            $query->where('business_id', $businessId);
                        }
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export')
                    ->action(function () use ($service) {
                        $rows = $this->getFilteredTableQuery()
                            ->with(['user', 'branch', 'business', 'recordedBy'])
                            ->orderByDesc('checked_in_at')
                            ->get();

                        return response()->streamDownload(function () use ($rows, $service) {
                            $handle = fopen('php://output', 'w');
                            fputcsv($handle, ['Staff', 'Organisation', 'Branch', 'Date', 'Arrival', 'Departure', 'Total', 'Recorded by']);
                            foreach ($rows as $row) {
                                $item = $service->transform($row);
                                fputcsv($handle, [
                                    $item['staff']['name'],
                                    $item['organisation'],
                                    $item['branch'],
                                    $item['attendance_date'],
                                    $item['arrival'],
                                    $item['departure'],
                                    $item['total'],
                                    $item['recorded_by'],
                                ]);
                            }
                            fclose($handle);
                        }, 'staff-attendance.csv');
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('correct')
                    ->label('Correct')
                    ->visible(fn () => auth()->user()?->canCorrectStaffAttendance())
                    ->form([
                        DateTimePicker::make('checked_in_at')
                            ->label('Arrival')
                            ->required()
                            ->seconds(false)
                            ->timezone(fn (?StaffAttendance $record) => $record?->business?->timezoneName() ?? config('app.timezone')),
                        DateTimePicker::make('checked_out_at')
                            ->label('Departure')
                            ->seconds(false)
                            ->timezone(fn (?StaffAttendance $record) => $record?->business?->timezoneName() ?? config('app.timezone')),
                        Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->rows(3),
                    ])
                    ->fillForm(fn (StaffAttendance $record) => [
                        'checked_in_at' => $record->checked_in_at,
                        'checked_out_at' => $record->checked_out_at,
                    ])
                    ->action(function (StaffAttendance $record, array $data) use ($service) {
                        $timezone = $record->business?->timezoneName() ?? (string) config('app.timezone', 'Africa/Nairobi');
                        $checkedIn = Carbon::parse($data['checked_in_at'], $timezone)->utc();
                        $checkedOut = filled($data['checked_out_at'] ?? null)
                            ? Carbon::parse($data['checked_out_at'], $timezone)->utc()
                            : null;

                        if ($checkedOut && $checkedOut->lt($checkedIn)) {
                            Notification::make()
                                ->title('Departure must be after arrival.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $service->correct(auth()->user(), $record, $checkedIn, $checkedOut, trim((string) $data['reason']));

                        Notification::make()
                            ->title('Attendance corrected. The original times were kept.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public function render()
    {
        return view('livewire.school-management.staff-attendance-management');
    }
}
