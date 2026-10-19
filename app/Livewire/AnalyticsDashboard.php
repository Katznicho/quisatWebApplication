<?php

namespace App\Livewire;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use App\Models\Business;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AnalyticsDashboard extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    public $dateFilter = 'all_time';
    public $startDate = null;
    public $endDate = null;
    public $showCustomRange = false;

    public function updatedDateFilter()
    {
        if ($this->dateFilter !== 'custom_range') {
            $this->showCustomRange = false;
            $this->startDate = null;
            $this->endDate = null;
        } else {
            $this->showCustomRange = true;
        }
    }

    public function updatedStartDate()
    {
        // Ensure end date doesn't exceed current date
        if ($this->endDate && $this->endDate > now()->format('Y-m-d')) {
            $this->endDate = now()->format('Y-m-d');
        }
        // Ensure start date doesn't exceed end date
        if ($this->endDate && $this->startDate > $this->endDate) {
            $this->startDate = $this->endDate;
        }
    }

    public function updatedEndDate()
    {
        // Ensure end date doesn't exceed current date
        if ($this->endDate && $this->endDate > now()->format('Y-m-d')) {
            $this->endDate = now()->format('Y-m-d');
        }
        // Ensure start date doesn't exceed end date
        if ($this->startDate && $this->startDate > $this->endDate) {
            $this->startDate = $this->endDate;
        }
    }

    public function getFilterDisplayProperty()
    {
        [$startDate, $endDate] = $this->getDateRange();
        
        switch ($this->dateFilter) {
            case 'today':
                return 'Today (' . Carbon::now()->format('M d, Y') . ')';
            case 'this_week':
                return 'This Week (' . Carbon::now()->startOfWeek()->format('M d') . ' - ' . Carbon::now()->endOfWeek()->format('M d, Y') . ')';
            case 'this_month':
                return 'This Month (' . Carbon::now()->startOfMonth()->format('M d') . ' - ' . Carbon::now()->endOfMonth()->format('M d, Y') . ')';
            case 'this_quarter':
                return 'This Quarter (' . Carbon::now()->startOfQuarter()->format('M d') . ' - ' . Carbon::now()->endOfQuarter()->format('M d, Y') . ')';
            case 'this_year':
                return 'This Year (' . Carbon::now()->startOfYear()->format('M d, Y') . ' - ' . Carbon::now()->endOfYear()->format('M d, Y') . ')';
            case 'custom_range':
                if ($startDate && $endDate) {
                    return Carbon::parse($startDate)->format('M d, Y') . ' - ' . Carbon::parse($endDate)->format('M d, Y');
                }
                return 'Custom Range';
            default:
                return 'All Time';
        }
    }

    protected function getDateRange()
    {
        $now = Carbon::now();
        
        switch ($this->dateFilter) {
            case 'today':
                return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
            case 'this_week':
                return [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()];
            case 'this_month':
                return [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];
            case 'this_quarter':
                return [$now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()];
            case 'this_year':
                return [$now->copy()->startOfYear(), $now->copy()->endOfYear()];
            case 'custom_range':
                if ($this->startDate && $this->endDate) {
                    $end = Carbon::parse($this->endDate);
                    // Ensure end date doesn't exceed today
                    if ($end->isFuture()) {
                        $end = Carbon::now();
                    }
                    return [
                        Carbon::parse($this->startDate)->startOfDay(),
                        $end->endOfDay()
                    ];
                }
                return [null, null];
            default: // all_time
                return [null, null];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                [$startDate, $endDate] = $this->getDateRange();
                
                $query = Business::withCount(['users']);
                
                
                return $query;
            })
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-business.png'))
                    ->url(function ($record) {
                        if (!$record->logo) {
                            return asset('images/default-business.png');
                        }
                        return asset('storage/' . $record->logo);
                    })
                    ->openUrlInNewTab(),
                
                TextColumn::make('name')
                    ->label('Business Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->url(fn ($record) => route('analytics.business.show', $record->uuid))
                    ->color('primary'),
                
                TextColumn::make('account_balance')
                    ->label('Balance')
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . ' UGX')
                    ->sortable()
                    ->color(fn ($state) => $state < 10000 ? 'danger' : 'success'),
                
                TextColumn::make('cost_per_sms')
                    ->label('Cost/SMS')
                    ->formatStateUsing(fn ($state) => number_format($state, 0) . ' UGX')
                    ->sortable(),
                
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->paginated([10, 25, 50, 100]);
    }

    public function render(): View
    {
        [$startDate, $endDate] = $this->getDateRange();
        
        // Calculate overall statistics with date filtering
        $businessQuery = Business::query();
        if ($startDate && $endDate) {
            $businessQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalBusinesses = $businessQuery->count();
        
        $userQuery = \App\Models\User::query();
        if ($startDate && $endDate) {
            $userQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalUsers = $userQuery->count();
        
        // Total balance: sum of businesses created within date range (or all if no filter)
        $balanceQuery = Business::query();
        if ($startDate && $endDate) {
            $balanceQuery->whereBetween('created_at', [$startDate, $endDate]);
        }
        $totalBalance = $balanceQuery->sum('account_balance');

        return view('livewire.analytics-dashboard', [
            'totalBusinesses' => $totalBusinesses,
            'totalUsers' => $totalUsers,
            'totalBalance' => $totalBalance,
        ]);
    }
}
