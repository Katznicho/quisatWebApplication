<?php

namespace App\Livewire\SchoolManagement;

use App\Models\PickupCode;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PickupCodesManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $today = Carbon::now(config('app.timezone', 'Africa/Nairobi'))->toDateString();

        return $table
            ->query(
                PickupCode::query()
                    ->with(['student:id,first_name,last_name,class_room_id', 'student.classRoom:id,name'])
                    ->where('business_id', Auth::user()->business_id)
                    ->whereDate('code_date', $today)
            )
            ->columns([
                Tables\Columns\TextColumn::make('student.full_name')->label('Child'),
                Tables\Columns\TextColumn::make('student.classRoom.name')->label('Group'),
                Tables\Columns\TextColumn::make('code')->label('Pickup code')->copyable(),
                Tables\Columns\IconColumn::make('used_at')->label('Released')->boolean(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->label('Expires'),
            ])
            ->defaultSort('id', 'desc')
            ->heading('Today’s pickup codes')
            ->description('Share the 4-digit code with the parent at drop-off. Staff must enter it before the child is released.');
    }

    public function render(): View
    {
        return view('livewire.school-management.pickup-codes-management');
    }
}
