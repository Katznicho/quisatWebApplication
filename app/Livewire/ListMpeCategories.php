<?php

namespace App\Livewire;

use App\Models\MpeCategory;
use App\Services\CustomerSoapClient;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Contracts\TranslatableContentDriver;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ListMpeCategories extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public function makeFilamentTranslatableContentDriver(): ?TranslatableContentDriver
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(MpeCategory::query())
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->sortable()
                    ->searchable(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Create Category')
                    ->icon('heroicon-o-plus')
                    ->schema([
                        TextInput::make('code')
                            ->required()
                            ->maxLength(50),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(255),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $customerNo = trim((string) session('customerNumber'));

                            if ($customerNo === '') {
                                Notification::make()
                                    ->title('Please log in again')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $result = app(CustomerSoapClient::class)->call('CreateCustomerMPECategory', [
                                'customerNo' => $customerNo,
                                'createJson' => json_encode([
                                    'code' => $data['code'],
                                    'name' => $data['name'],
                                    'description' => $data['description'] ?? '',
                                ]),
                            ]);

                            $success = $result !== false
                                && $result !== null
                                && strtolower((string) $result) !== 'false';

                            if (! $success) {
                                Notification::make()
                                    ->title('Failed to create category')
                                    ->body(is_string($result) && $result !== '' ? $result : 'The service rejected the request.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('MPE category created')
                                ->success()
                                ->send();

                            $this->resetTable();
                        } catch (\Throwable $th) {
                            Notification::make()
                                ->title('Failed to create category')
                                ->body($th->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->filters([])
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        return view('livewire.list-mpe-categories');
    }
}
