<?php

namespace App\Livewire\ClinicPatients\Concerns;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

trait DisablesBrowserAutocomplete
{
    /**
     * Stop the browser from suggesting values saved from other Quisat forms
     * (class names, module titles, etc.) in clinic fields.
     */
    protected function noBrowserAutocomplete(array $extra = []): array
    {
        return array_merge([
            'autocomplete' => 'off',
            'autocorrect' => 'off',
            'autocapitalize' => 'off',
            'spellcheck' => 'false',
            'data-1p-ignore' => 'true',
            'data-lpignore' => 'true',
        ], $extra);
    }

    protected function clinicTextInput(string $field): TextInput
    {
        return TextInput::make($field)
            ->extraInputAttributes($this->noBrowserAutocomplete([
                // Unique token so Chrome does not match generic "name" history.
                'autocomplete' => 'qui-clinic-'.$field,
            ]));
    }

    protected function clinicTextarea(string $field, int $rows = 3): Textarea
    {
        return Textarea::make($field)
            ->extraInputAttributes($this->noBrowserAutocomplete([
                'autocomplete' => 'qui-clinic-'.$field,
            ]))
            ->rows($rows)
            ->columnSpanFull();
    }
}
