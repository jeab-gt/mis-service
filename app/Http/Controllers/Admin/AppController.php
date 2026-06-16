<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\OptionSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AppController extends Controller
{
    public function index()
    {
        $apps = App::withCount('submissions')->latest()->paginate(12);
        return view('apps.index', compact('apps'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('apps.form', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:100|unique:apps,slug|alpha_dash',
            'category'    => 'required|string|max:50',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
            'is_active'   => 'boolean',
            'form_schema' => 'required|json',
            'flow_schema' => 'required|json',
        ]);

        $data['is_active']   = $request->boolean('is_active', true);
        $data['created_by']  = auth()->id();
        $data['form_schema'] = json_decode($data['form_schema'], true);
        $data['flow_schema'] = json_decode($data['flow_schema'], true);

        $app = App::create($data);
        $this->syncSteps($app);

        return redirect()->route('admin.apps.index')->with('success', 'สร้าง App สำเร็จ');
    }

    public function edit(App $app)
    {
        $roles = Role::all();
        return view('apps.form', compact('app', 'roles'));
    }

    public function update(Request $request, App $app)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'required|string|max:100|unique:apps,slug,' . $app->id . '|alpha_dash',
            'category'    => 'required|string|max:50',
            'description' => 'nullable|string',
            'icon'        => 'nullable|string|max:50',
            'is_active'   => 'boolean',
            'form_schema' => 'required|json',
            'flow_schema' => 'required|json',
        ]);

        $data['is_active']   = $request->boolean('is_active', true);
        $data['form_schema'] = json_decode($data['form_schema'], true);
        $data['flow_schema'] = json_decode($data['flow_schema'], true);

        $app->update($data);
        $this->syncSteps($app);

        return redirect()->route('admin.apps.index')->with('success', 'แก้ไข App สำเร็จ');
    }

    public function destroy(App $app)
    {
        $app->delete();
        return back()->with('success', 'ลบ App สำเร็จ');
    }

    public function designer(App $app)
    {
        $roles = Role::all();
        $optionSets = OptionSet::select('id', 'code', 'name_th', 'name_en', 'source_type')->get();
        return view('apps.designer', compact('app', 'roles', 'optionSets'));
    }

    public function flow(App $app)
    {
        $roles = Role::all();
        $optionSets = OptionSet::select('id', 'code', 'name_th', 'name_en')->get();
        return view('apps.flow', compact('app', 'roles', 'optionSets'));
    }

    public function saveFlow(Request $request, App $app): JsonResponse
    {
        $request->validate(['flow_schema' => 'required|array']);
        $app->update(['flow_schema' => $request->flow_schema]);
        $this->syncSteps($app);
        return response()->json(['message' => 'Flow saved successfully']);
    }

    public function preview(App $app)
    {
        return view('apps.preview', compact('app'));
    }

    protected function syncSteps(App $app): void
    {
        $schema = $app->flow_schema;

        // Graph-based flow: no approval_steps table entries needed
        if (isset($schema['nodes']) && !empty($schema['nodes'])) {
            return;
        }

        // Legacy linear flow
        $app->approvalSteps()->delete();
        $steps = $schema['steps'] ?? [];
        foreach ($steps as $step) {
            $role = \Spatie\Permission\Models\Role::where('name', $step['role'] ?? '')->first();
            if ($role) {
                $app->approvalSteps()->create([
                    'step_order'       => $step['step_order'],
                    'name_th'          => $step['name_th'],
                    'name_en'          => $step['name_en'],
                    'approver_role_id' => $role->id,
                    'action_type'      => $step['action_type'] ?? 'any_one',
                    'sla_hours'        => $step['sla_hours'] ?? null,
                ]);
            }
        }
    }
}
