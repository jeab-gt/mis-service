@extends('layouts.app')
@section('title', 'Flow Designer — ' . $flow->name)
@section('breadcrumb')
<a href="{{ route('admin.flows.index') }}" class="hover:text-indigo-600">Flow Library</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $flow->name }}</span>
@endsection

@section('content')
<style>
#drawflow {
    width: 100%; height: 100%;
    background-color: #f9fafb;
    background-image: radial-gradient(circle, #d1d5db 1px, transparent 1px);
    background-size: 24px 24px;
}
.dark #drawflow {
    background-color: #111827;
    background-image: radial-gradient(circle, #374151 1px, transparent 1px);
}
.drawflow-node {
    padding: 0 !important;
    border-radius: 10px !important;
    border: 2px solid #e5e7eb !important;
    min-width: 150px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    background: #fff !important;
}
.drawflow-node.selected {
    border-color: #6366f1 !important;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.25), 0 2px 8px rgba(0,0,0,0.12) !important;
}
.drawflow_content_node { padding: 0 !important; }
.drawflow-node .title-box { display: none !important; }
.df-node-type {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.5px; color: #fff; padding: 4px 10px;
    border-radius: 8px 8px 0 0;
}
.df-node-name { font-size: 13px; font-weight: 500; color: #111827; padding: 7px 10px; min-height: 30px; }
.df-node-sla { font-size: 11px; color: #6b7280; padding: 0 10px 4px; }
.df-node-ports {
    display: flex; justify-content: space-between;
    padding: 4px 10px 7px; border-top: 1px solid #f3f4f6;
    font-size: 10px; font-weight: 600;
}
.df-port-a { color: #16a34a; }
.df-port-r { color: #dc2626; }
.drawflow-node .input, .drawflow-node .output {
    background: #6366f1 !important;
    border: 2px solid #fff !important;
    width: 14px !important; height: 14px !important;
    box-shadow: 0 1px 4px rgba(0,0,0,0.25) !important;
}
.drawflow-node.approval .output.output_1 { background: #22c55e !important; }
.drawflow-node.approval .output.output_2 { background: #ef4444 !important; }
.connection .main-path { stroke: #6366f1 !important; stroke-width: 2.5px !important; }
</style>

<div x-data="flowDesigner(
    {!! htmlspecialchars(json_encode([
        'nodes' => $flow->nodes->map(fn($n) => [
            'node_id'                  => $n->node_id,
            'type'                     => $n->type,
            'name_th'                  => $n->name_th,
            'name_en'                  => $n->name_en,
            'approver_source'          => $n->approver_source,
            'approver_role_id'         => $n->approver_role_id,
            'approver_option_set_code' => $n->approver_option_set_code,
            'scope'                    => $n->scope,
            'action_type'              => $n->action_type,
            'sla_hours'                => $n->sla_hours,
            'step_form_template_id'    => $n->step_form_template_id,
            'pos_x'                    => $n->pos_x,
            'pos_y'                    => $n->pos_y,
        ])->values(),
        'edges' => $flow->edges->map(fn($e) => [
            'from_node_id' => $e->from_node_id,
            'to_node_id'   => $e->to_node_id,
            'label'        => $e->label,
        ])->values(),
    ]), ENT_QUOTES, 'UTF-8') !!},
    {!! htmlspecialchars(json_encode($roles->map(fn($r)=>['id'=>$r->id,'name'=>$r->name])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!},
    {!! htmlspecialchars(json_encode($stepForms->map(fn($t)=>['id'=>$t->id,'name'=>$t->name])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!},
    '{{ route('admin.flows.save', $flow) }}'
)" class="flex flex-col h-[calc(100vh-9rem)]">

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h1 class="text-lg font-bold">{{ $flow->name }} — Flow Designer</h1>
        <div class="flex items-center space-x-2">
            <span x-show="saveStatus" x-text="saveStatus"
                  :class="saveStatus === 'Saved!' ? 'text-green-600' : 'text-red-600'"
                  class="text-sm font-medium" x-cloak></span>
            <button @click="clearCanvas" class="btn-secondary text-sm">
                <i class="ti ti-trash mr-1"></i>Clear
            </button>
            <button @click="saveFlow" class="btn-primary text-sm">
                <i class="ti ti-device-floppy mr-1"></i>Save
            </button>
        </div>
    </div>

    <div class="flex gap-3 flex-1 min-h-0">

        <!-- Left: Node Palette -->
        <div class="w-44 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-3 flex flex-col space-y-2 overflow-y-auto mis-card">
            <p class="text-xs font-semibold text-gray-500 uppercase">Node Types</p>
            <p class="text-xs text-gray-400 -mt-1 mb-1">ลากหรือคลิกเพื่อเพิ่ม</p>
            @foreach([
                ['type'=>'start',           'label'=>'Start',               'color'=>'bg-gray-500',   'icon'=>'ti-player-play'],
                ['type'=>'approval',        'label'=>'Approval Step',       'color'=>'bg-blue-500',   'icon'=>'ti-checkbox'],
                ['type'=>'end_approved',    'label'=>'End: Approved',       'color'=>'bg-green-500',  'icon'=>'ti-circle-check'],
                ['type'=>'end_rejected',    'label'=>'End: Rejected',       'color'=>'bg-red-500',    'icon'=>'ti-circle-x'],
                ['type'=>'return_revision', 'label'=>'Return for Revision', 'color'=>'bg-yellow-500', 'icon'=>'ti-corner-up-left'],
            ] as $nt)
            <div draggable="true"
                 @dragstart="dragType = '{{ $nt['type'] }}'"
                 @click="addNode('{{ $nt['type'] }}')"
                 class="cursor-pointer select-none rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-2.5 text-center hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                <div class="w-8 h-8 {{ $nt['color'] }} text-white rounded-lg flex items-center justify-center mx-auto mb-1.5">
                    <i class="ti {{ $nt['icon'] }} text-sm"></i>
                </div>
                <p class="text-xs font-medium leading-tight">{{ $nt['label'] }}</p>
            </div>
            @endforeach
        </div>

        <!-- Center: Drawflow Canvas -->
        <div class="flex-1 min-w-0 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden"
             @dragover.prevent
             @drop="dropOnCanvas($event)">
            <div id="drawflow" class="w-full h-full"></div>
        </div>

        <!-- Right: Node Properties -->
        <div class="w-64 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto mis-card"
             x-show="selectedNodeId !== null" x-cloak>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">Node Properties</h3>
                <button @click="selectedNodeId = null" class="text-gray-400 hover:text-gray-600">
                    <i class="ti ti-x text-sm"></i>
                </button>
            </div>
            <template x-if="selectedNode">
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="form-label text-xs">Node ID</label>
                        <input type="text" x-model="selectedNode.node_id"
                               @change="syncSelectedNode()"
                               class="form-input text-sm font-mono" placeholder="unique_id">
                    </div>
                    <div>
                        <label class="form-label text-xs">ชื่อ (TH)</label>
                        <input type="text" x-model="selectedNode.name_th"
                               @input="syncSelectedNode()"
                               class="form-input text-sm">
                    </div>
                    <div>
                        <label class="form-label text-xs">Name (EN)</label>
                        <input type="text" x-model="selectedNode.name_en"
                               @input="syncSelectedNode()"
                               class="form-input text-sm">
                    </div>

                    <template x-if="selectedNode.type === 'approval'">
                        <div class="space-y-3">
                            <div>
                                <label class="form-label text-xs">Approver Source</label>
                                <select x-model="selectedNode.approver_source"
                                        @change="syncSelectedNode()"
                                        class="form-select text-sm">
                                    <option value="role">Role</option>
                                    <option value="specific_user">Specific User</option>
                                    <option value="option_set">Option Set</option>
                                </select>
                            </div>
                            <template x-if="selectedNode.approver_source === 'role'">
                                <div>
                                    <label class="form-label text-xs">Role</label>
                                    <select x-model="selectedNode.approver_role_id"
                                            @change="syncSelectedNode()"
                                            class="form-select text-sm">
                                        <option value="">-- เลือก Role --</option>
                                        <template x-for="r in roles" :key="r.id">
                                            <option :value="r.id" x-text="r.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                            <template x-if="selectedNode.approver_source === 'option_set'">
                                <div>
                                    <label class="form-label text-xs">Option Set Code</label>
                                    <input type="text" x-model="selectedNode.approver_option_set_code"
                                           @change="syncSelectedNode()"
                                           class="form-input text-sm">
                                </div>
                            </template>
                            <div>
                                <label class="form-label text-xs">Scope</label>
                                <select x-model="selectedNode.scope"
                                        @change="syncSelectedNode()"
                                        class="form-select text-sm">
                                    <option value="own_factory">Own Factory</option>
                                    <option value="parent_factory">Parent Factory</option>
                                    <option value="any_factory">Any Factory</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">Action Type</label>
                                <select x-model="selectedNode.action_type"
                                        @change="syncSelectedNode()"
                                        class="form-select text-sm">
                                    <option value="any_one">Any One</option>
                                    <option value="all_must">All Must</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">SLA (hours)</label>
                                <input type="number" min="0"
                                       x-model="selectedNode.sla_hours"
                                       @change="syncSelectedNode()"
                                       class="form-input text-sm">
                            </div>
                            <div>
                                <label class="form-label text-xs">Step Form Template</label>
                                <select x-model="selectedNode.step_form_template_id"
                                        @change="syncSelectedNode()"
                                        class="form-select text-sm">
                                    <option value="">-- ไม่มี --</option>
                                    <template x-for="f in stepForms" :key="f.id">
                                        <option :value="f.id" x-text="f.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-400 mt-1" x-show="selectedNode.step_form_template_id">
                                    Approver กรอก form นี้ขณะ approve
                                </p>
                            </div>
                        </div>
                    </template>

                    <div class="pt-2 border-t border-gray-200 dark:border-gray-600">
                        <button @click="removeSelectedNode()"
                                class="w-full text-xs px-3 py-1.5 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors">
                            <i class="ti ti-trash mr-1"></i>ลบ Node นี้
                        </button>
                    </div>
                </div>
            </template>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function flowDesigner(initialFlow, roles, stepForms, saveUrl) {
    return {
        editor: null,
        roles,
        stepForms,
        selectedNodeId: null,
        nodeData: {},
        saveStatus: '',
        dragType: null,

        nodeConfig: {
            start:           { inputs: 0, outputs: 1, color: '#6b7280', label: 'Start' },
            approval:        { inputs: 1, outputs: 2, color: '#3b82f6', label: 'Approval' },
            end_approved:    { inputs: 1, outputs: 0, color: '#22c55e', label: 'End: Approved' },
            end_rejected:    { inputs: 1, outputs: 0, color: '#ef4444', label: 'End: Rejected' },
            return_revision: { inputs: 1, outputs: 1, color: '#f59e0b', label: 'Return for Revision' },
        },

        get selectedNode() {
            if (this.selectedNodeId === null) return null;
            return this.nodeData[this.selectedNodeId] ?? null;
        },

        init() {
            window.__flowApp = this;        // expose for Playwright / debug
            const container = document.getElementById('drawflow');
            this.editor = new window.Drawflow(container);
            this.editor.reroute = true;
            this.editor.start();

            this.editor.on('nodeSelected', (id) => {
                this.selectedNodeId = id;
            });
            this.editor.on('nodeUnselected', () => {
                this.selectedNodeId = null;
            });
            this.editor.on('nodeRemoved', (id) => {
                delete this.nodeData[id];
                if (this.selectedNodeId === id) this.selectedNodeId = null;
            });

            // Defer until after the browser has computed flex layout,
            // so the precanvas has non-zero clientWidth for connection path calculation.
            requestAnimationFrame(() => requestAnimationFrame(() => {
                this.loadFlow(initialFlow);
            }));
        },

        buildNodeHtml(type, data) {
            const cfg  = this.nodeConfig[type] || { color: '#6b7280', label: type };
            const name = data.name_th || data.name_en || '';
            const nameHtml = name
                ? `<div class="df-node-name">${this._esc(name)}</div>`
                : `<div class="df-node-name" style="color:#9ca3af;font-style:italic;">ตั้งชื่อ...</div>`;
            const slaHtml = data.sla_hours
                ? `<div class="df-node-sla">SLA: ${data.sla_hours}h</div>`
                : '';
            const portsHtml = type === 'approval'
                ? `<div class="df-node-ports"><span class="df-port-a">✓ approve</span><span class="df-port-r">✗ reject</span></div>`
                : '';
            return `<div class="df-node-inner"><div class="df-node-type" style="background:${cfg.color}">${cfg.label}</div>${nameHtml}${slaHtml}${portsHtml}</div>`;
        },

        _esc(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        },

        addNodeToCanvas(type, pos_x, pos_y, data) {
            const cfg     = this.nodeConfig[type] || { inputs: 1, outputs: 1 };
            const node_id = data.node_id || (type + '_' + Date.now().toString(36));

            const nodeObj = {
                node_id,
                type,
                name_th:                  data.name_th  ?? '',
                name_en:                  data.name_en  ?? '',
                approver_source:          data.approver_source          ?? 'role',
                approver_role_id:         data.approver_role_id         ?? null,
                approver_option_set_code: data.approver_option_set_code ?? null,
                scope:                    data.scope       ?? 'own_factory',
                action_type:              data.action_type ?? 'any_one',
                sla_hours:                data.sla_hours   ?? null,
                step_form_template_id:    data.step_form_template_id ?? null,
            };

            const html = this.buildNodeHtml(type, nodeObj);
            const dfId = this.editor.addNode(type, cfg.inputs, cfg.outputs, pos_x, pos_y, type, nodeObj, html);
            this.nodeData[dfId] = nodeObj;
            return dfId;
        },

        addNode(type) {
            const pos_x = 160 + Math.random() * 380;
            const pos_y = 80  + Math.random() * 180;
            const dfId  = this.addNodeToCanvas(type, pos_x, pos_y, {});
            this.selectedNodeId = dfId;
        },

        dropOnCanvas(event) {
            const type = this.dragType || event.dataTransfer.getData('nodeType');
            if (!type) return;
            const rect  = document.getElementById('drawflow').getBoundingClientRect();
            const zoom  = this.editor.zoom || 1;
            const pos_x = (event.clientX - rect.left  - this.editor.canvas_x) / zoom;
            const pos_y = (event.clientY - rect.top   - this.editor.canvas_y) / zoom;
            this.addNodeToCanvas(type, pos_x, pos_y, {});
            this.dragType = null;
        },

        syncSelectedNode() {
            if (this.selectedNodeId === null) return;
            const data   = this.nodeData[this.selectedNodeId];
            if (!data) return;
            const dfNode = this.editor.drawflow.drawflow.Home.data[this.selectedNodeId];
            if (dfNode) {
                dfNode.data = { ...data };
                const contentEl = document.querySelector(`#node-${this.selectedNodeId} .drawflow_content_node`);
                if (contentEl) contentEl.innerHTML = this.buildNodeHtml(data.type, data);
            }
        },

        removeSelectedNode() {
            if (this.selectedNodeId === null) return;
            this.editor.removeNodeId('node-' + this.selectedNodeId);
        },

        loadFlow(initialFlow) {
            const nodeIdMap = {};
            for (const node of (initialFlow.nodes || [])) {
                const dfId = this.addNodeToCanvas(
                    node.type,
                    node.pos_x != null ? node.pos_x : 100,
                    node.pos_y != null ? node.pos_y : 100,
                    node
                );
                nodeIdMap[node.node_id] = dfId;
            }
            for (const edge of (initialFlow.edges || [])) {
                const fromId = nodeIdMap[edge.from_node_id];
                const toId   = nodeIdMap[edge.to_node_id];
                if (fromId != null && toId != null) {
                    const outPort = edge.label === 'reject' ? 'output_2' : 'output_1';
                    try {
                        this.editor.addConnection(fromId, toId, outPort, 'input_1');
                    } catch (e) {
                        console.warn('addConnection skipped:', edge, e);
                    }
                }
            }
        },

        clearCanvas() {
            if (!confirm('ล้าง canvas ทั้งหมด?')) return;
            this.editor.import({ drawflow: { Home: { data: {} } } });
            this.nodeData       = {};
            this.selectedNodeId = null;
        },

        async saveFlow() {
            this.saveStatus = 'Saving…';
            const dfData = this.editor.drawflow.drawflow.Home.data;
            const nodes        = [];
            const edges        = [];
            const dfIdToNodeId = {};

            for (const [dfId, dfNode] of Object.entries(dfData)) {
                const d = dfNode.data || {};
                dfIdToNodeId[dfId] = d.node_id;
                nodes.push({
                    node_id:                  d.node_id,
                    type:                     d.type,
                    name_th:                  d.name_th  || null,
                    name_en:                  d.name_en  || null,
                    approver_source:          d.approver_source          || null,
                    approver_role_id:         d.approver_role_id         || null,
                    approver_option_set_code: d.approver_option_set_code || null,
                    scope:                    d.scope       || 'own_factory',
                    action_type:              d.action_type || 'any_one',
                    sla_hours:                d.sla_hours   || null,
                    step_form_template_id:    d.step_form_template_id || null,
                    pos_x:                    dfNode.pos_x,
                    pos_y:                    dfNode.pos_y,
                });
            }

            for (const [dfId, dfNode] of Object.entries(dfData)) {
                const fromNodeId = dfIdToNodeId[dfId];
                const nodeType   = (dfNode.data || {}).type;
                for (const [outputKey, outputData] of Object.entries(dfNode.outputs || {})) {
                    const label = nodeType === 'approval'
                        ? (outputKey === 'output_1' ? 'approve' : 'reject')
                        : null;
                    for (const conn of (outputData.connections || [])) {
                        const toNodeId = dfIdToNodeId[conn.node];
                        if (fromNodeId && toNodeId) {
                            edges.push({ from_node_id: fromNodeId, to_node_id: toNodeId, label });
                        }
                    }
                }
            }

            try {
                const res = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ nodes, edges }),
                });
                if (res.ok) {
                    this.saveStatus = 'Saved!';
                } else {
                    console.error('Save error:', await res.text());
                    this.saveStatus = 'Error!';
                }
            } catch (e) {
                console.error(e);
                this.saveStatus = 'Error!';
            }
            setTimeout(() => { this.saveStatus = ''; }, 3000);
        },
    };
}
</script>
@endpush
