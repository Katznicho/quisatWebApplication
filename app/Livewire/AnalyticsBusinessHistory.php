<?php

namespace App\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;

class AnalyticsBusinessHistory extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    public $businessId;

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return Activity::with(['user', 'subject'])
                    ->where('business_id', $this->businessId);
            })
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),
                
                TextColumn::make('description')
                    ->label('Activity')
                    ->wrap()
                    ->limit(100),
                
                TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->badge()
                    ->color('info'),
                
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        return view('livewire.analytics-business-history');
    }
}

