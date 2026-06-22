<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppCategory;
use App\Models\ChecksheetTemplate;
use App\Models\Dashboard;
use App\Models\Flow;
use App\Models\Master;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class ChecksheetTemplateController extends Controller
{
    public function index()
    {
        $templates = ChecksheetTemplate::withCount(['parameters', 'records'])
            ->with('creator')
            ->latest()
            ->paginate(12);

        return view('admin.checksheets.index', compact('templates'));
    }

    public function create()
    {
        $flows = Flow::where('is_active', true)->orderBy('name')->get();

        return view('admin.checksheets.create', compact('flows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'frequency'     => 'required|in:realtime,hourly,daily,weekly,monthly',
            'flow_id'       => 'nullable|exists:flows,id',
            'factory_scope' => 'required|in:own_factory,all_factories',
            'is_active'     => 'boolean',
        ]);

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i    = 1;
        while (ChecksheetTemplate::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $template = ChecksheetTemplate::create([
            'name'          => $data['name'],
            'description'   => $data['description'] ?? null,
            'slug'          => $slug,
            'frequency'     => $data['frequency'],
            'flow_id'       => $data['flow_id'] ?? null,
            'factory_scope' => $data['factory_scope'],
            'is_active'     => $request->boolean('is_active', true),
            'created_by'    => auth()->id(),
        ]);

        return redirect()->route('admin.checksheets.builder', $template)
            ->with('success', 'สร้าง Template สำเร็จ กรุณาตั้งค่า Parameters ด้านล่าง');
    }

    public function edit(ChecksheetTemplate $template)
    {
        $flows      = Flow::where('is_active', true)->orderBy('name')->get();
        $categories = AppCategory::orderBy('sort_order')->orderBy('name_th')->get();
        $dashboards = Dashboard::orderBy('name')->get();
        $roles      = Role::orderBy('name')->get();
        $factories  = Master::where('type', 'factory')->orderBy('name_th')->get();

        return view('admin.checksheets.edit', compact('template', 'flows', 'categories', 'dashboards', 'roles', 'factories'));
    }

    public function builder(ChecksheetTemplate $template)
    {
        $template->load(['parameters', 'timeSlots', 'flow']);
        $flows = Flow::where('is_active', true)->orderBy('name')->get();

        return view('admin.checksheets.builder', compact('template', 'flows'));
    }

    public function save(Request $request, ChecksheetTemplate $template)
    {
        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'description'   => 'nullable|string',
            'frequency'     => 'sometimes|required|in:realtime,hourly,daily,weekly,monthly',
            'flow_id'       => 'nullable|exists:flows,id',
            'factory_scope' => 'sometimes|required|in:own_factory,all_factories',
            'is_active'     => 'boolean',
            'category_id'        => 'nullable|exists:app_categories,id',
            'primary_dashboard_id'       => 'nullable|exists:dashboards,id',
            'allowed_roles'      => 'nullable|array',
            'allowed_roles.*'    => 'string',
            'allowed_factories'  => 'nullable|array',
            'allowed_factories.*'=> 'integer',
            'parameters'    => 'nullable|array',
            'parameters.*.id'          => 'nullable|integer',
            'parameters.*.name'        => 'required|string|max:255',
            'parameters.*.unit'        => 'nullable|string|max:50',
            'parameters.*.type'        => 'required|in:number,text,boolean,enum,pass_fail',
            'parameters.*.options'     => 'nullable|array',
            'parameters.*.spec_min'    => 'nullable|numeric',
            'parameters.*.spec_max'    => 'nullable|numeric',
            'parameters.*.spec_target' => 'nullable|numeric',
            'parameters.*.alert_on'    => 'nullable|in:above_max,below_min,both,none',
            'parameters.*.alert_level' => 'nullable|in:warning,critical',
            'parameters.*.sort_order'  => 'nullable|integer',
            'parameters.*.is_active'   => 'boolean',
            'time_slots'               => 'nullable|array',
            'time_slots.*.label'       => 'required|string|max:100',
            'time_slots.*.sort_order'  => 'nullable|integer',
        ]);

        // Update template settings if provided
        $templateUpdate = [];
        foreach (['name', 'description', 'frequency', 'flow_id', 'factory_scope', 'category_id', 'primary_dashboard_id'] as $field) {
            if (array_key_exists($field, $data)) {
                $templateUpdate[$field] = $data[$field];
            }
        }
        if ($request->has('is_active')) {
            $templateUpdate['is_active'] = $request->boolean('is_active');
        }
        if ($request->has('allowed_roles')) {
            $templateUpdate['allowed_roles'] = empty($data['allowed_roles']) ? null : $data['allowed_roles'];
        }
        if ($request->has('allowed_factories')) {
            $raw = $data['allowed_factories'] ?? [];
            $templateUpdate['allowed_factories'] = empty($raw) ? null : array_map('intval', (array) $raw);
        }
        if (!empty($templateUpdate)) {
            $template->update($templateUpdate);
        }

        // Sync parameters — update existing (by ID), create new, delete removed
        if (isset($data['parameters'])) {
            $incomingIds = collect($data['parameters'])->pluck('id')->filter()->map(fn($id) => (int) $id)->values();
            $existingIds = $template->parameters()->pluck('id');

            // Delete parameters that are no longer in the list
            $toDeleteIds = $existingIds->diff($incomingIds);
            if ($toDeleteIds->isNotEmpty()) {
                // Must delete child records first to satisfy FK constraints
                \App\Models\ChecksheetDailySummary::whereIn('parameter_id', $toDeleteIds)->delete();
                \App\Models\ChecksheetRecordValue::whereIn('parameter_id', $toDeleteIds)->delete();
                $template->parameters()->whereIn('id', $toDeleteIds)->delete();
            }

            foreach ($data['parameters'] as $i => $paramData) {
                $paramId = isset($paramData['id']) ? (int) $paramData['id'] : null;
                $slug    = \Illuminate\Support\Str::slug($paramData['name'] . '-' . ($i + 1));
                $attrs   = [
                    'name'        => $paramData['name'],
                    'slug'        => $slug,
                    'unit'        => $paramData['unit'] ?? null,
                    'type'        => $paramData['type'],
                    'options'     => $paramData['options'] ?? null,
                    'spec_min'    => $paramData['spec_min'] ?? null,
                    'spec_max'    => $paramData['spec_max'] ?? null,
                    'spec_target' => $paramData['spec_target'] ?? null,
                    'alert_on'    => $paramData['alert_on'] ?? 'both',
                    'alert_level' => $paramData['alert_level'] ?? 'warning',
                    'sort_order'  => $paramData['sort_order'] ?? $i,
                    'is_active'   => isset($paramData['is_active']) ? (bool) $paramData['is_active'] : true,
                ];

                if ($paramId && $existingIds->contains($paramId)) {
                    // Update in place — preserves historical data FK references
                    $template->parameters()->where('id', $paramId)->update($attrs);
                } else {
                    $template->parameters()->create($attrs);
                }
            }
        }

        // Sync time slots
        if (isset($data['time_slots'])) {
            $template->timeSlots()->delete();
            foreach ($data['time_slots'] as $i => $slotData) {
                $template->timeSlots()->create([
                    'label'      => $slotData['label'],
                    'sort_order' => $slotData['sort_order'] ?? $i,
                ]);
            }
        }

        return response()->json([
            'success'  => true,
            'message'  => 'บันทึกสำเร็จ',
            'template' => $template->fresh(['parameters', 'timeSlots']),
        ]);
    }

    public function destroy(ChecksheetTemplate $template)
    {
        $template->delete();

        return redirect()->route('admin.checksheets.index')
            ->with('success', 'ลบ Template สำเร็จ');
    }
}
