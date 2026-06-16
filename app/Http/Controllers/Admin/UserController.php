<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Master;
use App\Models\User;
use App\Models\UserFactoryRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $query    = User::with(['roles', 'section', 'section.parent', 'factory']);

        // Factory-scoped access: only super_admin or parent-factory it_manager see all
        if (! $authUser->hasRole('super_admin') && ! ($authUser->is_parent_factory && $authUser->hasRole('it_manager'))) {
            if ($authUser->factory_id) {
                $query->where('factory_id', $authUser->factory_id);
            }
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('employee_code', 'like', "%$search%");
            });
        }
        if ($role = $request->get('role')) {
            $query->role($role);
        }
        if ($factoryId = $request->get('factory_id')) {
            $query->where('factory_id', $factoryId);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $users     = $query->orderBy('name')->paginate(15)->withQueryString();
        $roles     = Role::all();
        $factories = Master::byType('factory')->active()->orderBy('sort_order')->get();

        return view('users.index', compact('users', 'roles', 'factories'));
    }

    public function create()
    {
        $roles     = Role::all();
        $factories = Master::byType('factory')->active()->orderBy('sort_order')->get();
        $sections  = collect();
        return view('users.form', compact('roles', 'factories', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'name_th'          => 'nullable|string|max:255',
            'name_en'          => 'nullable|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'password'         => 'required|min:8|confirmed',
            'employee_code'    => 'required|string|max:50|unique:users,employee_code',
            'factory_id'       => 'nullable|exists:masters,id',
            'section_id'       => 'nullable|exists:masters,id',
            'phone'            => 'nullable|string|max:20',
            'is_active'        => 'boolean',
            'is_parent_factory' => 'boolean',
            'roles'            => 'required|array',
            'factory_roles'    => 'nullable|array',
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'name_th'           => $data['name_th'] ?? null,
            'name_en'           => $data['name_en'] ?? null,
            'email'             => $data['email'],
            'password'          => bcrypt($data['password']),
            'employee_code'     => $data['employee_code'],
            'factory_id'        => $data['factory_id'] ?? null,
            'section_id'        => $data['section_id'] ?? null,
            'phone'             => $data['phone'] ?? null,
            'is_active'         => $request->boolean('is_active', true),
            'is_parent_factory' => $request->boolean('is_parent_factory'),
        ]);

        $user->syncRoles($data['roles']);
        $this->syncFactoryRoles($user, $data['factory_id'] ?? null, $request->input('factory_roles', []));

        return redirect()->route('admin.users.index')->with('success', 'สร้างผู้ใช้งานสำเร็จ');
    }

    public function edit(User $user)
    {
        $roles     = Role::all();
        $factories = Master::byType('factory')->active()->orderBy('sort_order')->get();
        $sections  = $user->factory_id
            ? Master::where('factory_id', $user->factory_id)->where('type', 'section')->active()->orderBy('name_th')->get()
            : collect();

        $factoryRoleIds = $user->factory_id
            ? UserFactoryRole::where('user_id', $user->id)->where('factory_id', $user->factory_id)->pluck('role_id')->toArray()
            : [];

        return view('users.form', compact('user', 'roles', 'factories', 'sections', 'factoryRoleIds'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            $request->merge(['is_active' => true]);
        }

        $rules = [
            'name'              => 'required|string|max:255',
            'name_th'           => 'nullable|string|max:255',
            'name_en'           => 'nullable|string|max:255',
            'email'             => 'required|email|unique:users,email,' . $user->id,
            'employee_code'     => 'required|string|max:50|unique:users,employee_code,' . $user->id,
            'factory_id'        => 'nullable|exists:masters,id',
            'section_id'        => 'nullable|exists:masters,id',
            'phone'             => 'nullable|string|max:20',
            'roles'             => 'required|array',
            'factory_roles'     => 'nullable|array',
            'is_parent_factory' => 'boolean',
        ];

        if ($request->filled('password')) {
            $rules['password'] = 'min:8|confirmed';
        }

        $data = $request->validate($rules);

        $updateData = [
            'name'              => $data['name'],
            'name_th'           => $data['name_th'] ?? null,
            'name_en'           => $data['name_en'] ?? null,
            'email'             => $data['email'],
            'employee_code'     => $data['employee_code'],
            'factory_id'        => $data['factory_id'] ?? null,
            'section_id'        => $data['section_id'] ?? null,
            'phone'             => $data['phone'] ?? null,
            'is_active'         => $user->id === auth()->id() ? true : $request->boolean('is_active', true),
            'is_parent_factory' => $user->id === auth()->id() ? $user->is_parent_factory : $request->boolean('is_parent_factory'),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($request->password);
        }

        $user->update($updateData);
        $user->syncRoles($data['roles']);
        $this->syncFactoryRoles($user, $data['factory_id'] ?? null, $request->input('factory_roles', []));

        return redirect()->route('admin.users.index')->with('success', 'แก้ไขผู้ใช้งานสำเร็จ');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'ไม่สามารถลบตัวเองได้');
        }
        $user->delete();
        return back()->with('success', 'ลบผู้ใช้งานสำเร็จ');
    }

    /** AJAX: return sections/teams/lines belonging to a factory */
    public function sectionsForFactory(int $factoryId): JsonResponse
    {
        $sections = Master::where('factory_id', $factoryId)
            ->whereIn('type', ['section', 'team', 'line'])
            ->where('is_active', true)
            ->orderBy('name_th')
            ->get(['id', 'name_th', 'name_en', 'type']);

        return response()->json($sections);
    }

    private function syncFactoryRoles(User $user, ?int $factoryId, array $roleIds): void
    {
        if (! $factoryId) {
            return;
        }
        UserFactoryRole::where('user_id', $user->id)->where('factory_id', $factoryId)->delete();
        foreach ($roleIds as $roleId) {
            if ($roleId) {
                UserFactoryRole::create(['user_id' => $user->id, 'factory_id' => $factoryId, 'role_id' => $roleId]);
            }
        }
    }
}
