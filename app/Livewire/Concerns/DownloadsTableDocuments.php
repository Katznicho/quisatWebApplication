<?php

namespace App\Livewire\Concerns;

use App\Models\ClassRoom;
use App\Services\TableDocumentExporter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

trait DownloadsTableDocuments
{
    protected function tableDocumentActions(string $title): array
    {
        return [
            ActionGroup::make([
                Action::make('downloadExcel')
                    ->label('Excel')
                    ->action(fn () => $this->downloadTableDocument('excel', $title)),
                Action::make('downloadWord')
                    ->label('Word')
                    ->action(fn () => $this->downloadTableDocument('word', $title)),
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->action(fn () => $this->downloadTableDocument('pdf', $title)),
            ])
                ->label('Download')
                ->icon('heroicon-o-arrow-down-tray')
                ->button(),
        ];
    }

    protected function downloadTableDocument(string $format, string $title)
    {
        [$headings, $rows] = $this->visibleTableDocument();

        return app(TableDocumentExporter::class)->download($format, $title, $headings, $rows);
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

    private function visibleTableDocument(): array
    {
        $columns = collect($this->getTable()->getColumns())
            ->filter(fn (Column $column) => ! $column instanceof ImageColumn)
            ->reject(function (Column $column) {
                try {
                    return $column->isToggledHidden();
                } catch (\Throwable) {
                    return false;
                }
            })
            ->values();

        $headings = $columns->map(fn (Column $column) => (string) $column->getLabel())->all();
        $records = $this->getFilteredTableQuery()->limit(5000)->get();
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
