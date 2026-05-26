<?php

namespace App\Livewire\ClinicPatients;

use App\Models\ClinicPatient;
use App\Models\ClinicPatientDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class PatientDocumentsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public ClinicPatient $patient;

    public function mount(ClinicPatient $patient): void
    {
        $this->patient = $patient;
        $this->authorizePatient();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ClinicPatientDocument::query()
                    ->where('clinic_patient_id', $this->patient->id)
                    ->latest()
            )
            ->heading('Documents')
            ->description('Upload and manage medical files, referrals, test results, and consent forms.')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('type')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => str_replace('_', ' ', ucfirst($state))),
                TextColumn::make('description')
                    ->limit(40)
                    ->placeholder('No description')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('File size')
                    ->formatStateUsing(fn (?int $state): string => $this->formatSize($state))
                    ->toggleable(),
                TextColumn::make('uploader.name')
                    ->label('Uploaded by')
                    ->placeholder('Staff')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload document')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('primary')
                    ->form([
                        TextInput::make('title')
                            ->placeholder('e.g. Blood test result - May 2026')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->options([
                                'lab_result' => 'Lab result',
                                'prescription' => 'Prescription',
                                'consent_form' => 'Consent form',
                                'referral' => 'Referral',
                                'medical_report' => 'Medical report',
                                'other' => 'Other',
                            ])
                            ->placeholder('Select document type')
                            ->default('other')
                            ->required(),
                        Textarea::make('description')
                            ->placeholder('Short description of what this file contains')
                            ->rows(3)
                            ->columnSpanFull(),
                        FileUpload::make('file_path')
                            ->label('File')
                            ->disk('public')
                            ->directory('clinic-patient-documents')
                            ->required()
                            ->preserveFilenames()
                            ->helperText('Upload a PDF, image, or Word document for this patient.')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'image/webp',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            ]),
                    ])
                    ->action(function (array $data): void {
                        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                        $disk = Storage::disk('public');
                        $fullPath = $disk->path($data['file_path']);

                        ClinicPatientDocument::create([
                            'business_id' => $this->patient->business_id,
                            'clinic_patient_id' => $this->patient->id,
                            'uploaded_by' => auth()->id(),
                            'type' => $data['type'],
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                            'file_path' => $data['file_path'],
                            'mime_type' => $disk->mimeType($data['file_path']) ?: null,
                            'size' => file_exists($fullPath) ? filesize($fullPath) : null,
                        ]);
                    }),
            ])
            ->actions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ClinicPatientDocument $record): string => $record->file_url)
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->action(function (ClinicPatientDocument $record): void {
                        if ($record->file_path && Storage::disk('public')->exists($record->file_path)) {
                            Storage::disk('public')->delete($record->file_path);
                        }

                        $record->delete();
                    }),
            ])
            ->emptyStateHeading('No patient documents yet')
            ->emptyStateDescription('Upload the first file for this patient record.')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    protected function formatSize(?int $bytes): string
    {
        if (empty($bytes)) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return sprintf('%.1f %s', $value, $units[$power]);
    }

    protected function authorizePatient(): void
    {
        $businessId = auth()->user()?->business_id;
        if (! $businessId || $this->patient->business_id !== $businessId) {
            abort(403);
        }
    }

    public function render(): View
    {
        return view('livewire.clinic-patients.patient-documents-table');
    }
}
