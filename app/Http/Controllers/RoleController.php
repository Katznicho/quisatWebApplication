<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Traits\AccessTrait;
use App\Traits\AuditTrait;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use AccessTrait;
    use AuditTrait;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return view('roles.index');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occurred while trying to view roles');
        }
    }

    public function create()
    {
        $roles = $this->getAccessControl();

        // Filter out the 'Entities' related permissions if the user is not an admin
        // if (auth()->user()->role_id != 1) {
        //     unset($roles['Entities']);
        // }

        $permissions = [];
        // $entities = Entity::all();
        // $groups = Group::all();  // Fetch all groups

        // if (auth()->user()->role_id != 1) {
        //     $entities = Entity::where('id', auth()->user()->entity_id)->get();
        // }

        return view('roles.create', compact('roles', 'permissions'));
    }

    public function edit(Role $role)
    {
        $permissions = json_decode($role->permissions, true);
        if ($permissions == null) {
            $permissions = [];
        }
        $roles = $this->getAccessControl();

        // if (auth()->user()->role_id != 1) {
        //     unset($roles['Entities']);
        // }

        // Fetch all groups and groups associated with this role
        // $groups = Group::all();
        // $roleGroups = $role->groups->pluck('id')->toArray();  // Get group IDs

        return view('roles.edit', compact('roles', 'permissions', 'role'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (auth()->user()->role_id == 1) {
            $request->validate([
                // 'name' => 'required|string|max:255',
                'name' => 'required|string|unique:roles,name',
                'permissions_menu' => 'required',
                // 'entity_id' => 'required',
                // 'groups' => 'required|array',  // Validate that groups are selected
            ]);
        } else {
            $request->validate([
                // 'name' => 'required|string|max:255',
                'name' => 'required|string|unique:roles,name',
                'permissions_menu' => 'required',
                // 'groups' => 'required|array',  // Validate that groups are selected
            ]);
        }

        // dd($request->all());

        try {
            if (auth()->user()->role_id == 1) {
                // DB::beginTransaction();
                $role = Role::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'permissions' => json_encode($request->permissions_menu),
                    'user_id' => auth()->user()->id,
                    'entity_id' => $request->entity_id,
                ]);
            } else {
                $role = Role::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'permissions' => json_encode($request->permissions_menu),
                    'user_id' => auth()->user()->id,
                    // 'entity_id' => auth()->user()->entity->id,
                ]);
            }

            // Attach the selected groups to the role
            // $role->groups()->sync($request->groups);

            $this->createAudit($request, 'Created Role', 'Create', $role->getTable(), $role->id);
            return redirect()->route('roles.index')->with('success', 'Role created successfully.');
        } catch (\Throwable $th) {
            // DB::rollBack();
            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        // Retrieve permissions associated with the role
        $staff_permissions = json_decode($role->permissions);
        if ($staff_permissions == NULL) {
            $staff_permissions = array();
        }
        $roles = $this->getAccessControl();

        return view('roles.show', compact('roles', 'staff_permissions', 'role'));
    }

    /** Show the form for editing the specified resource. */
    // public function edit(Role $role)
    // {
    //     $permissions =  json_decode($role->permissions);
    //     if ($permissions == NULL) {
    //         $permissions = array();
    //     }
    //     $roles = $this->getAccessControl();
    //     return view('roles.edit', compact('roles', 'permissions', 'role'));
    // }

    /** Update the specified resource in storage. */
    // public function update(Request $request, Role $role)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'permissions_menu' => 'required',
    //     ]);

    //     try {
    //         $role->update([
    //             'name' => $request->name,
    //             'description' => $request->description,
    //             'permissions' => json_encode($request->permissions_menu),
    //             'user_id' => auth()->user()->id,
    //             'entity_id' => auth()->user()->entity->id,
    //         ]);

    //         $this->createAudit($request, 'Updated Role', 'Update', $role->getTable(), $role->id);
    //         return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    //     } catch (\Throwable $th) {
    //         return redirect()->back()->with('error', 'An error occurred while trying to update the role');
    //     }
    // }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions_menu' => 'required',
            // 'groups' => 'required|array',  // Validate groups
        ]);

        try {
            $role->update([
                'name' => $request->name,
                'description' => $request->description,
                'permissions' => json_encode($request->permissions_menu),
                'user_id' => auth()->user()->id,
                // 'entity_id' => auth()->user()->entity->id,
            ]);

            // Sync the selected groups to the role
            // $role->groups()->sync($request->groups);

            $this->createAudit($request, 'Updated Role', 'Update', $role->getTable(), $role->id);
            return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occurred while trying to update the role');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            // detach all permissions
            $request = request();
            $role->delete();
            $this->createAudit($request, 'Deleted Role', 'Delete', $role->getTable(), $role->id);
            return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'An error occurred while trying to delete the role');
        }
    }
    //write a function to get all roles
    
}
