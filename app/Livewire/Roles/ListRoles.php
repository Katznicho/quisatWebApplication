<?php

namespace App\Livewire\Roles;

use App\Models\Role;
use App\Models\Business;
use App\Traits\AccessTrait;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class ListRoles extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;
    use AccessTrait;

    // Helper: sanitize permission string to slug for form keys
    protected static function permissionKey(string $permission): string
    {
        return Str::slug($permission, '_');
    }

    // Build permissions schema for checkboxes, passing which keys are checked, and whether checkboxes should be disabled (for view)
    protected function buildPermissionCheckboxes(array $permissions, array $checkedPermissions = [], array &$permissionMap = [], bool $disabled = false): array
    {
        $schema = [];

        foreach ($permissions as $group => $categories) {
            $categoryCheckboxes = [];

            foreach ($categories as $category => $perms) {
                $permCheckboxes = [];

                foreach ($perms as $perm) {
                    $key = self::permissionKey($perm);
                    $permissionMap[$key] = $perm;

                    $permCheckboxes[] = Checkbox::make($key)
                        ->label($perm)
                        ->default($checkedPermissions[$key] ?? false)
                        ->disabled($disabled);
                }

                $categoryCheckboxes[] = Fieldset::make(Str::slug($category, '_'))
                    ->label($category)
                    ->schema($permCheckboxes)
                    ->columns(1);
            }

            $schema[] = Fieldset::make(Str::slug($group, '_'))
                ->label($group)
                ->schema($categoryCheckboxes)
                ->columns(1);
        }

        return $schema;
    }

    // Load checked permission keys from Role model's stored permissions JSON
    protected function loadPermissionsIntoForm(Role $role, array $permissionMap): array
{
    $checked = [];
    $rolePermissions = $role->permissions ?? [];

    foreach ($permissionMap as $key => $perm) {
        if (in_array($perm, $rolePermissions)) {
            $checked[$key] = true;
        }
    }

    return $checked;
}


    public function table(Table $table): Table
    {
        $query = Role::query()->latest();

        if (Auth::check() && Auth::user()->business_id !== 1) {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Role Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('business.name')
                    ->label('Business')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                ...(Auth::check() && Auth::user()->business_id === 1 ? [
                    Tables\Filters\SelectFilter::make('business_id')
                        ->label('Filter by Business')
                        ->options(Business::pluck('name', 'id'))
                        ->searchable()
                        ->multiple(),
                ] : []),
            ])
            ->actions([
                // View action opens a modal, readonly fields
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('View Role')
                    ->modalWidth('lg')
                    ->form(function (Role $record) {
                        $permissionMap = [];
                        $checkedPermissions = $this->loadPermissionsIntoForm($record, $permissionMap);
                        $permissionsSchema = $this->buildPermissionCheckboxes(self::getAccessControl(), $checkedPermissions, $permissionMap, true); // disabled checkboxes

                        return [
                            Select::make('business_id')
                                ->label('Business')
                                ->options(Business::pluck('name', 'id'))
                                ->disabled()
                                ->default($record->business_id),

                            TextInput::make('name')
                                ->label('Role Name')
                                ->disabled()
                                ->default($record->name),

                            Textarea::make('description')
                                ->label('Description')
                                ->disabled()
                                ->default($record->description),

                            Grid::make()
                                ->schema($permissionsSchema),
                        ];
                    }),

                EditAction::make()
                    ->modalHeading('Edit Role')
                    ->form(function (Role $record) {
                        $permissionMap = [];
                        $checkedPermissions = $this->loadPermissionsIntoForm($record, $permissionMap);
                        $permissionsSchema = $this->buildPermissionCheckboxes(self::getAccessControl(), $checkedPermissions, $permissionMap);

                        return [
                            Select::make('business_id')
                                ->label('Business')
                                ->placeholder('Select a business')
                                ->options(Business::pluck('name', 'id'))
                                ->required()
                                ->disabled(fn() => Auth::user()->business_id !== 1)
                                ->default($record->business_id)
                                ->dehydrated(), // Ensure it's included in form data even when disabled

                            TextInput::make('name')
                                ->label('Role Name')
                                ->placeholder('Enter role name')
                                ->required()
                                ->default($record->name),

                            Textarea::make('description')
                                ->label('Description')
                                ->placeholder('Enter description')
                                ->nullable()
                                ->default($record->description),

                            Grid::make()
                                ->schema($permissionsSchema),
                        ];
                    })
                    ->mutateFormDataUsing(function (array $data, Role $record) {
                        $permissionMap = [];
                        $this->buildPermissionCheckboxes(self::getAccessControl(), [], $permissionMap);

                        $selectedPermissions = [];
                        foreach ($permissionMap as $key => $perm) {
                            if (!empty($data[$key])) {
                                $selectedPermissions[] = $perm;
                            }
                            unset($data[$key]);
                        }
                        $data['permissions'] = json_encode($selectedPermissions);
                        
                        // Ensure business_id is set when field is disabled
                        if (!isset($data['business_id']) || empty($data['business_id'])) {
                            $data['business_id'] = $record->business_id ?? Auth::user()->business_id;
                        }
                        
                        return $data;
                    })
                    ->action(function (Role $record, array $data) {
                        $record->update($data);

                        Notification::make()
                            ->title('Role updated successfully.')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->modalHeading('Delete Role')
                    ->successNotificationTitle('Role deleted successfully (soft).'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Create Role')
                    ->modalHeading('Add New Role')
                    ->form(function () {
                        $permissionMap = [];
                        $permissionsSchema = $this->buildPermissionCheckboxes(self::getAccessControl(), [], $permissionMap);

                        return [
                            Select::make('business_id')
                                ->label('Business')
                                ->placeholder('Select a business')
                                ->options(Business::pluck('name', 'id'))
                                ->required()
                                ->default(Auth::user()->business_id)
                                ->disabled(fn() => Auth::user()->business_id !== 1)
                                ->dehydrated(), // Ensure it's included in form data even when disabled

                            TextInput::make('name')
                                ->label('Role Name')
                                ->placeholder('Enter role name')
                                ->required(),

                            Textarea::make('description')
                                ->label('Description')
                                ->placeholder('Enter description')
                                ->nullable(),

                            Grid::make()
                                ->schema($permissionsSchema),
                        ];
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        $permissionMap = [];
                        $this->buildPermissionCheckboxes(self::getAccessControl(), [], $permissionMap);

                        $selectedPermissions = [];
                        foreach ($permissionMap as $key => $perm) {
                            if (!empty($data[$key])) {
                                $selectedPermissions[] = $perm;
                            }
                            unset($data[$key]);
                        }
                        $data['permissions'] = json_encode($selectedPermissions);
                        
                        // Ensure business_id is set when field is disabled
                        if (!isset($data['business_id']) || empty($data['business_id'])) {
                            $data['business_id'] = Auth::user()->business_id;
                        }
                        
                        return $data;
                    })
                    ->action(function (array $data) {
                        $role = new Role();
                        $role->fill($data);
                        $role->save();

                        Notification::make()
                            ->title('Role created successfully.')
                            ->success()
                            ->send();
                    })
                    ->createAnother(false),
            ]);
    }

    public function render(): View
    {
        return view('livewire.roles.list-roles');
    }
}
