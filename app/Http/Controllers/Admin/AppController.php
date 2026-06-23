<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppCategory;
use App\Models\ChecksheetTemplate;
use App\Models\Dashboard;
use App\Models\Flow;
use App\Models\FormTemplate;
use App\Models\Master;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AppController extends Controller
{
    public function index()
    {
        $apps = App::with(['appCategory', 'initialFormTemplate', 'flow'])
            ->withCount('submissions')
            ->latest()
            ->get();

        $checksheets = ChecksheetTemplate::with(['category'])
            ->withCount(['parameters', 'records'])
            ->latest()
            ->get();

        $canEdit   = auth()->user()->can('app.edit');
        $canDelete = auth()->user()->can('app.delete');

        $allItems = $apps->map(fn($a) => [
            'id'                => $a->id,
            'type'              => 'form',
            'name'              => $a->name,
            'is_active'         => (bool) $a->is_active,
            'category_name'     => $a->appCategory?->name_th ?? 'ไม่ระบุหมวดหมู่',
            'icon'              => $a->icon ?? 'ti-file-text',
            'submissions_count' => $a->submissions_count,
            'created_ts'        => $a->created_at?->timestamp ?? 0,
            'edit_url'          => route('admin.apps.edit', $a->slug),
            'form_url'          => $a->initialFormTemplate ? route('admin.form-templates.designer', $a->initialFormTemplate) : null,
            'flow_url'          => $a->flow ? route('admin.flows.designer', $a->flow) : null,
            'delete_url'        => route('admin.apps.destroy', $a->slug),
            'delete_label'      => 'ลบ App "' . $a->name . '"?',
        ])->merge($checksheets->map(fn($c) => [
            'id'               => $c->id,
            'type'             => 'checksheet',
            'name'             => $c->name,
            'is_active'        => (bool) $c->is_active,
            'category_name'    => $c->category?->name_th ?? 'ไม่ระบุหมวดหมู่',
            'icon'             => 'ti-clipboard-list',
            'parameters_count' => $c->parameters_count,
            'records_count'    => $c->records_count,
            'frequency'        => $c->frequency,
            'created_ts'       => $c->created_at?->timestamp ?? 0,
            'edit_url'         => route('admin.checksheets.edit', $c->slug),
            'builder_url'      => route('admin.checksheets.builder', $c->slug),
            'records_url'      => route('checksheets.records', $c->slug),
            'delete_url'       => route('admin.checksheets.destroy', $c->slug),
            'delete_label'     => 'ลบ Checksheet "' . $c->name . '"?',
        ]))->values();

        return view('apps.index', compact('allItems', 'canEdit', 'canDelete'));
    }

    public function create(Request $request)
    {
        if ($request->get('type') === 'checksheet') {
            return redirect()->route('admin.checksheets.create');
        }

        $formTemplates = FormTemplate::where('is_active', true)->orderBy('name')->get();
        $flows         = Flow::where('is_active', true)->orderBy('name')->get();
        $categories    = AppCategory::orderBy('sort_order')->orderBy('name_th')->get();
        $dashboards    = Dashboard::orderBy('name')->get();
        $roles         = Role::orderBy('name')->get();
        $factories     = Master::where('type', 'factory')->orderBy('name_th')->get();

        return view('apps.edit', compact('formTemplates', 'flows', 'categories', 'dashboards', 'roles', 'factories') + ['app' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'slug'                      => 'required|string|max:100|unique:apps,slug|alpha_dash',
            'category'                  => 'required|string|max:50',
            'category_id'               => 'nullable|exists:app_categories,id',
            'primary_dashboard_id'      => 'nullable|exists:dashboards,id',
            'description'               => 'nullable|string',
            'icon'                      => 'nullable|string|max:50',
            'is_active'                 => 'boolean',
            'initial_form_template_id'  => 'nullable|exists:form_templates,id',
            'revision_form_template_id' => 'nullable|exists:form_templates,id',
            'flow_id'                   => 'nullable|exists:flows,id',
            'allowed_roles'             => 'nullable|array',
            'allowed_roles.*'           => 'string',
            'allowed_factories'         => 'nullable|array',
            'allowed_factories.*'       => 'integer',
        ]);

        $data['is_active']         = $request->boolean('is_active', true);
        $data['created_by']        = auth()->id();
        $data['allowed_roles']     = empty($data['allowed_roles']) ? null : $data['allowed_roles'];
        $data['allowed_factories'] = empty($data['allowed_factories']) ? null : array_map('intval', (array)($data['allowed_factories'] ?? []));

        App::create($data);

        return redirect()->route('admin.apps.index')->with('success', 'สร้าง App สำเร็จ');
    }

    public function edit(App $app)
    {
        $formTemplates = FormTemplate::where('is_active', true)->orderBy('name')->get();
        $flows         = Flow::where('is_active', true)->orderBy('name')->get();
        $categories    = AppCategory::orderBy('sort_order')->orderBy('name_th')->get();
        $dashboards    = Dashboard::orderBy('name')->get();
        $roles         = Role::orderBy('name')->get();
        $factories     = Master::where('type', 'factory')->orderBy('name_th')->get();

        return view('apps.edit', compact('app', 'formTemplates', 'flows', 'categories', 'dashboards', 'roles', 'factories'));
    }

    public function update(Request $request, App $app)
    {
        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'slug'                      => 'required|string|max:100|unique:apps,slug,' . $app->id . '|alpha_dash',
            'category'                  => 'required|string|max:50',
            'category_id'               => 'nullable|exists:app_categories,id',
            'primary_dashboard_id'      => 'nullable|exists:dashboards,id',
            'description'               => 'nullable|string',
            'icon'                      => 'nullable|string|max:50',
            'is_active'                 => 'boolean',
            'initial_form_template_id'  => 'nullable|exists:form_templates,id',
            'revision_form_template_id' => 'nullable|exists:form_templates,id',
            'flow_id'                   => 'nullable|exists:flows,id',
            'allowed_roles'             => 'nullable|array',
            'allowed_roles.*'           => 'string',
            'allowed_factories'         => 'nullable|array',
            'allowed_factories.*'       => 'integer',
        ]);

        $data['is_active']         = $request->boolean('is_active', true);
        $data['allowed_roles']     = empty($data['allowed_roles']) ? null : $data['allowed_roles'];
        $data['allowed_factories'] = empty($data['allowed_factories']) ? null : array_map('intval', (array)($data['allowed_factories'] ?? []));

        $app->update($data);

        return redirect()->route('admin.apps.index')->with('success', 'แก้ไข App สำเร็จ');
    }

    public function destroy(App $app)
    {
        $app->delete();
        return back()->with('success', 'ลบ App สำเร็จ');
    }

    public function preview(App $app)
    {
        $app->load('initialFormTemplate');
        return view('apps.preview', compact('app'));
    }
}
