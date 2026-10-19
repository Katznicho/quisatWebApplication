<?php

namespace App\Livewire\Concerns;

use App\Models\ClassRoom;
use App\Services\TableDocumentExporter;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

trait DownloadsTableDocuments
{
    protected function tableDocumentActions(string $title): array
    {
        return [
            Action::make('downloadTable')
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->button()
                ->modalHeading('Download')
                ->modalSubmitActionLabel('Download')
                ->modalDescription(function () {
                    $count = $this->exportMatchCount();

                    return $count.' records match the current filters. Choose the columns, then download.';
                })
                ->form(function () {
                    $options = $this->exportColumnOptions();

                    return [
                        CheckboxList::make('columns')
                            ->label('Columns')
                            ->options($options)
                            ->default(array_keys($options))
                            ->required()
                            ->columns(2),
                        Select::make('format')
                            ->label('File')
                            ->options([
                                'excel' => 'Excel',
                                'csv' => 'CSV',
                                'pdf' => 'PDF',
                            ])
                            ->default('excel')
                            ->required(),
                    ];
                })
                ->action(function (array $data) use ($title) {
                    if ($this->exportMatchCount() === 0) {
                        Notification::make()
                            ->title('No records found. Please adjust your filters.')
                            ->warning()
                            ->send();

                        return null;
                    }

                    return $this->downloadTableDocument($data['format'], $title, $data['columns'] ?? []);
                }),
        ];
    }

    protected function downloadTableDocument(string $format, string $title, array $columns = [])
    {
        [$headings, $rows] = $this->visibleTableDocument($columns);

        return app(TableDocumentExporter::class)->download($format, $title, $headings, $rows);
    }

    protected function exportMatchCount(): int
    {
        try {
            return (int) $this->getFilteredTableQuery()->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    protected function exportColumnOptions(): array
    {
        return collect($this->exportColumns())
            ->mapWithKeys(fn (Column $column) => [$column->getName() => (string) $column->getLabel()])
            ->all();
    }

    protected function classGroupLabel(): string
    {
        $business = auth()->user()?->business;
        if ($business?->isChurch() && ! $business?->isSchool()) {
            return 'Group';
        }
        if ($business?->isChurch() && $business?->isSchool()) {
            return 'Class / Group';
        }

        return 'Class';
    }

    protected function orderByClassName(Builder $query, string $classColumn = 'class_room_id'): Builder
    {
        $table = $query->getModel()->getTable();

        return $query
            ->orderBy(
                ClassRoom::query()
                    ->select('name')
                    ->whereColumn('class_rooms.id', $table.'.'.$classColumn)
                    ->limit(1)
            )
            ->orderBy($table.'.id');
    }

    protected function classGroupFilter(string $name = 'class_room_id'): SelectFilter
    {
        $businessId = auth()->user()?->business_id;

        return SelectFilter::make($name)
            ->label($this->classGroupLabel())
            ->options(fn () => ClassRoom::query()
                ->when($businessId && (int) $businessId !== 1, fn (Builder $query) => $query->where('business_id', $businessId))
                ->orderBy('name')
                ->pluck('name', 'id'))
            ->searchable();
    }

    protected function classGroupWhereHasFilter(string $relation, string $column = 'class_room_id'): SelectFilter
    {
        return $this->classGroupFilter($relation.'_'.$column)
            ->query(function (Builder $query, array $data) use ($relation, $column): Builder {
                $value = $data['value'] ?? null;
                if (! $value) {
                    return $query;
                }

                return $query->whereHas($relation, fn (Builder $related) => $related->where($column, $value));
            });
    }

    private function exportColumns(): array
    {
        return collect($this->getTable()->getColumns())
            ->filter(fn (Column $column) => ! $column instanceof ImageColumn)
            ->reject(function (Column $column) {
                try {
                    return $column->isToggledHidden();
                } catch (\Throwable) {
                    return false;
                }
            })
            ->values()
            ->all();
    }

    private function visibleTableDocument(array $selected = []): array
    {
        $columns = collect($this->exportColumns())
            ->when($selected !== [], fn ($columns) => $columns->filter(fn (Column $column) => in_array($column->getName(), $selected, true)))
            ->values();

        $headings = $columns->map(fn (Column $column) => (string) $column->getLabel())->all();
        $records = $this->getFilteredTableQuery()->limit(20000)->get();
        $rows = [];

        foreach ($records as $record) {
            $line = [];
            foreach ($columns as $column) {
                try {
                    $state = $column->record($record)->getState();
                } catch (\Throwable) {
                    $state = '';
                }
                $line[] = is_bool($state) ? ($state ? 'Yes' : 'No') : $state;
            }
            $rows[] = $line;
        }

        return [$headings, $rows];
    }
}
