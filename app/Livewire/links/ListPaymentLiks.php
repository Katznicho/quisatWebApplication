<?php

namespace App\Livewire\Links;

use App\Models\PaymentLink;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ListPaymentLiks extends Component implements HasForms, HasTable
{
    use InteractsWithForms, InteractsWithTable;

    public function table(Table $table): Table
    {
        $query = PaymentLink::query()->latest();

        // If the user is NOT super admin (business_id !== 1), filter to their business
        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                // Tables\Columns\TextColumn::make('uuid')
                //     ->label('Link ID')
                //     ->copyable()
                //     ->searchable(),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('UGX')
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('status')
                //     ->badge()
                //     ->color(fn(string $state): string => match ($state) {
                //         'pending' => 'warning',
                //         'active' => 'success',
                //         'expired' => 'danger',
                //         default => 'gray',
                //     })
                //     ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_fixed')
                    ->label('Fixed Amount')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_customer_info_required')
                    ->label('Customer Info Required')
                    ->boolean(),

                Tables\Columns\TextColumn::make('website_url')
                    ->label('Website')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('redirect_url')
                    ->label('Redirect URL')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->label('Expires On')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([

                // Tables\Filters\SelectFilter::make('status')
                //     ->label('Status')
                //     ->options([
                //         'active' => 'Active',
                //         'pending' => 'Pending',
                //         'expired' => 'Expired',
                //     ])
                //     ->searchable()
                //     ->multiple(),

                // Only show business filter to business_id == 1
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'name')
                    ->searchable()
                    ->multiple()
                    ->visible(fn() => Auth::user()->business_id === 1),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Is Active'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn(PaymentLink $record) => route('payment-links.show', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->url(fn(PaymentLink $record) => route('payment-links.edit', $record)),
                    Tables\Actions\Action::make('copy')
    ->label('Copy Link')
    ->icon('heroicon-o-clipboard-document')
    ->action(function (PaymentLink $record) {
        // No need to dispatch browser event here.
        // Just return true or nothing.
    })
    ->dispatch('copy-to-clipboard', fn (PaymentLink $record) => [
        'url' => $record->redirect_url ?? url("/pay/{$record->uuid}"),
    ])
    ->color('gray'),

                Tables\Actions\Action::make('share')
                    ->label('Share')
                    ->icon('heroicon-o-share')
                    ->action(
                        fn(PaymentLink $record) =>
                        $this->dispatchBrowserEvent('share-link', [
                            'url' => $record->redirect_url ?? url("/pay/{$record->uuid}")
                        ])
                    )
                    ->color('primary'),
            ])
            ->bulkActions([
                // Optional bulk actions
            ]);
    }

    public function render(): View
    {
        return view('livewire.links.list-payment-liks');
    }
}
