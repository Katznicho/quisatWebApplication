<?php

namespace App\Livewire\Float;

use App\Models\FloatManagement;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;



class ListFloatManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = FloatManagement::query()->latest();

        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\ImageColumn::make('proof')
                    ->label('Proof Preview')
                    ->square()
                    ->size(80, 80)
                    ->toggleable()
                    ->getStateUsing(function (FloatManagement $record) {
                        if (!$record->proof) {
                            return null;
                        }
                        $path = $record->proof;
                        $ext = pathinfo($path, PATHINFO_EXTENSION);
                        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'])) {
                            return Storage::disk('public')->url($path);
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('proof')
                    ->label('Download Proof')
                    ->formatStateUsing(function ($state) {
                        if ($state && Storage::disk('public')->exists($state)) {
                            $url = Storage::disk('public')->url($state);
                            return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Download</a>";
                        }
                        return 'No Proof';
                    })
                    ->html()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('UGX')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date Loaded')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('channel')
                    ->label('Channel')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->searchable()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('channel')
                    ->label('Channel')
                    ->options([
                        'Bank - Deposit' => 'Bank - Deposit',
                        'Mobile Money' => 'Mobile Money',
                    ])
                    ->searchable(),

                Tables\Filters\SelectFilter::make('currency')
                    ->label('Currency')
                    ->options([
                        'UGX' => 'UGX',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(FloatManagement $record) => route('float-management.show', $record))
                // ->openUrlInNewTab()
                ,
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn(FloatManagement $record) => route('float-management.edit', $record))
                    ->visible(fn() => Auth::check() && Auth::user()->business_id === 1),

                //here
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-check-badge')
                    ->form([
                        Select::make('status')
                            ->label('New Status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                                'failed' => 'Failed',
                            ])
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Status Update')
                    ->modalDescription('Are you sure you want to update the status of this float? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, Update')
                    ->action(function (FloatManagement $record, array $data) {
                        $record->update(['status' => $data['status']]);

                        Notification::make()
                            ->title('Status Updated')
                            ->body('Float status updated to: ' . ucfirst($data['status']))
                            ->success()
                            ->send();
                    })
                    ->visible(fn() => Auth::check() && Auth::user()->business_id === 1),

                //here



            ])
            ->bulkActions([
                // Add bulk actions here if needed for admin
            ]);
    }

    public function render(): View
    {
        return view('livewire.float.list-float-management');
    }
}
