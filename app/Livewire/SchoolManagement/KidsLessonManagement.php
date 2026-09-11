<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ClassRoom;
use App\Models\KidsLesson;
use App\Services\KidsChurchNotificationService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class KidsLessonManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $businessId = Auth::user()->business_id;

        return $table
            ->query(KidsLesson::query()->where('business_id', $businessId))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'home_resource' => 'Home resource',
                        'pastor_devotional' => 'Pastor devotion',
                        default => 'Bible lesson',
                    }),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('classRoom.name')->label('Group'),
                Tables\Columns\TextColumn::make('lesson_date')->date(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Add lesson / resource')
                    ->form($this->formSchema())
                    ->mutateFormDataUsing(function (array $data) use ($businessId) {
                        $data['business_id'] = $businessId;
                        $data['created_by'] = Auth::id();
                        if (($data['status'] ?? 'published') === 'published') {
                            $data['published_at'] = now();
                        }

                        return $data;
                    })
                    ->after(function (KidsLesson $record) {
                        if ($record->status === 'published') {
                            app(KidsChurchNotificationService::class)->notifyLesson($record);
                        }
                    }),
            ])
            ->actions([
                EditAction::make()->form($this->formSchema()),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function formSchema(): array
    {
        return [
            Select::make('type')
                ->options([
                    'bible_lesson' => 'Weekly Bible lesson',
                    'home_resource' => 'Resources for home',
                    'pastor_devotional' => 'Pastor’s weekly devotion',
                ])
                ->required(),
            TextInput::make('title')->required()->maxLength(255),
            Select::make('class_room_id')
                ->label('Group (optional — leave blank for everyone)')
                ->options(ClassRoom::where('business_id', Auth::user()->business_id)->pluck('name', 'id'))
                ->searchable(),
            DatePicker::make('lesson_date')->label('Week / Sunday date'),
            Textarea::make('object_lesson')->label('Object lesson')->rows(3),
            Textarea::make('craft_supplies')->label('Craft supply list')->rows(3),
            Textarea::make('teaching_script')->label('Teaching script')->rows(5),
            Textarea::make('body')->label('Lesson recap / devotion text')->rows(4),
            TextInput::make('memory_verse')->maxLength(255),
            TextInput::make('scripture_ref')->label('Scripture reference')->maxLength(120),
            Textarea::make('family_challenge')->label('Family faith challenge')->rows(3),
            TextInput::make('video_url')->label('Video URL (optional)')->maxLength(500),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published (parents & teachers notified)',
                ])
                ->default('published')
                ->required(),
        ];
    }

    public function render(): View
    {
        return view('livewire.school-management.kids-lesson-management');
    }
}
