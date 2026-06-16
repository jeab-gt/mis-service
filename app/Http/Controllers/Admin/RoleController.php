<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy(fn($p) => explode('.', $p->name)[0]);
        return view('roles.form', compact('permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'สร้าง Role สำเร็จ');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy(fn($p) => explode('.', $p->name)[0]);
        $rolePerms   = $role->permissions->pluck('name')->toArray();
        return view('roles.form', compact('role', 'permissions', 'rolePerms'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('success', 'แก้ไข Role สำเร็จ');
    }

    public function destroy(Role $role)
    {
        if (in_array($role->name, ['super_admin', 'it_manager', 'it_staff', 'team_lead', 'requester'])) {
            return back()->with('error', 'ไม่สามารถลบ Role เริ่มต้นได้');
        }
        $role->delete();
        return back()->with('success', 'ลบ Role สำเร็จ');
    }
}
