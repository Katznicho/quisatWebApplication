<?php

namespace App\Livewire\SchoolManagement;

use App\Models\ClassRoom;
use App\Models\QuisatAlbum;
use App\Models\QuisatAlbumMedia;
use App\Services\QuisatAlbumNotificationService;
use Filament\Forms\Components\FileUpload;
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

class QuisatMomentsManagement extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public function table(Table $table): Table
    {
        $businessId = Auth::user()->business_id;

        return $table
            ->query(QuisatAlbum::query()->where('business_id', $businessId)->withCount('media'))
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('classRoom.name')->label('Class / Group'),
                Tables\Columns\TextColumn::make('media_count')->label('Photos'),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalHeading('Create album')
                    ->form($this->formSchema())
                    ->using(function (array $data) use ($businessId) {
                        return $this->persistAlbum($data, $businessId);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema(false))
                    ->using(function (array $data, QuisatAlbum $record) use ($businessId) {
                        return $this->persistAlbum($data, $businessId, $record);
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    protected function formSchema(bool $withPhotos = true): array
    {
        $fields = [
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->rows(3),
            Select::make('type')
                ->options([
                    'class_daily' => 'Class / daily',
                    'event' => 'Event',
                    'official_class_photo' => 'Official class photo',
                ])
                ->default('class_daily')
                ->required(),
            Select::make('class_room_id')
                ->label('Class / Group')
                ->options(ClassRoom::where('business_id', Auth::user()->business_id)->pluck('name', 'id'))
                ->searchable(),
            Select::make('status')
                ->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ])
                ->default('published')
                ->required(),
        ];

        if ($withPhotos) {
            $fields[] = FileUpload::make('photos')
                ->label('Photos')
                ->multiple()
                ->image()
                ->directory('quisat-moments')
                ->disk('public')
                ->maxFiles(40)
                ->maxSize(8192);
        }

        return $fields;
    }

    protected function persistAlbum(array $data, int $businessId, ?QuisatAlbum $album = null): QuisatAlbum
    {
        $photos = $data['photos'] ?? [];
        unset($data['photos']);

        $wasPublished = $album?->isPublished();

        $payload = [
            ...$data,
            'business_id' => $businessId,
            'created_by' => $album?->created_by ?: Auth::id(),
            'published_at' => ($data['status'] ?? 'published') === 'published'
                ? ($album?->published_at ?: now())
                : null,
        ];

        if ($album) {
            $album->update($payload);
        } else {
            $album = QuisatAlbum::create($payload);
        }

        foreach ((array) $photos as $index => $path) {
            if (! $path) {
                continue;
            }
            QuisatAlbumMedia::create([
                'album_id' => $album->id,
                'uploaded_by' => Auth::id(),
                'path' => $path,
                'media_type' => 'photo',
                'sort_order' => $album->media()->count() + $index + 1,
            ]);
        }

        if ($album->fresh()->isPublished() && ! $wasPublished) {
            app(QuisatAlbumNotificationService::class)->notifyPublished($album->fresh(['classRoom', 'media']));
        }

        return $album;
    }

    public function render(): View
    {
        return view('livewire.school-management.quisat-moments-management');
    }
}
