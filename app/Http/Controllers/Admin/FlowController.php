<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Flow;
use App\Models\FlowEdge;
use App\Models\FlowNode;
use App\Models\FormTemplate;
use App\Models\OptionSet;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class FlowController extends Controller
{
    public function index()
    {
        $flows = Flow::withCount('apps')->with('nodes')->latest()->get();
        return view('flows.index', compact('flows'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $flow = Flow::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.flows.designer', $flow)
            ->with('success', 'สร้าง Flow สำเร็จ');
    }

    public function designer(Flow $flow)
    {
        $roles      = Role::all();
        $optionSets = OptionSet::select('id', 'code', 'name_th', 'name_en')->get();
        $stepForms  = FormTemplate::where('category', 'step_form')->where('is_active', true)->get();

        $flow->load(['nodes', 'edges']);

        return view('flows.designer', compact('flow', 'roles', 'optionSets', 'stepForms'));
    }

    public function save(Request $request, Flow $flow)
    {
        $request->validate([
            'nodes' => 'required|array',
            'edges' => 'required|array',
        ]);

        // Replace nodes
        $flow->nodes()->delete();
        foreach ($request->nodes as $node) {
            FlowNode::create([
                'flow_id'                  => $flow->id,
                'node_id'                  => $node['node_id'],
                'type'                     => $node['type'],
                'name_th'                  => $node['name_th'] ?? null,
                'name_en'                  => $node['name_en'] ?? null,
                'approver_source'          => $node['approver_source'] ?? null,
                'approver_role_id'         => $node['approver_role_id'] ?? null,
                'approver_user_id'         => $node['approver_user_id'] ?? null,
                'approver_option_set_code' => $node['approver_option_set_code'] ?? null,
                'scope'                    => $node['scope'] ?? 'own_factory',
                'action_type'              => $node['action_type'] ?? 'any_one',
                'sla_hours'                => $node['sla_hours'] ?? null,
                'step_form_template_id'    => $node['step_form_template_id'] ?? null,
                'pos_x'                    => $node['pos_x'] ?? 0,
                'pos_y'                    => $node['pos_y'] ?? 0,
            ]);
        }

        // Replace edges
        $flow->edges()->delete();
        foreach ($request->edges as $edge) {
            FlowEdge::create([
                'flow_id'      => $flow->id,
                'from_node_id' => $edge['from_node_id'],
                'to_node_id'   => $edge['to_node_id'],
                'label'        => $edge['label'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Flow saved']);
    }

    public function duplicate(Flow $flow)
    {
        $newFlow = $flow->replicate(['id']);
        $newFlow->name       = $flow->name . ' (Copy)';
        $newFlow->created_by = auth()->id();
        $newFlow->save();

        foreach ($flow->nodes as $node) {
            $newNode = $node->replicate(['id']);
            $newNode->flow_id = $newFlow->id;
            $newNode->save();
        }
        foreach ($flow->edges as $edge) {
            $newEdge = $edge->replicate(['id']);
            $newEdge->flow_id = $newFlow->id;
            $newEdge->save();
        }

        return redirect()->route('admin.flows.designer', $newFlow)
            ->with('success', 'ทำสำเนา Flow สำเร็จ');
    }

    public function destroy(Flow $flow)
    {
        if ($flow->apps()->count() > 0) {
            return back()->withErrors(['error' => 'ไม่สามารถลบได้ เนื่องจากมี App ใช้งาน Flow นี้อยู่']);
        }
        $flow->delete();
        return back()->with('success', 'ลบ Flow สำเร็จ');
    }
}
