@extends('layouts.app')
@section('title', 'Flow Designer — ' . $app->name)
@section('breadcrumb')
<a href="{{ route('admin.apps.index') }}" class="hover:text-indigo-600">Apps</a>
<i class="ti ti-chevron-right text-xs"></i>
<a href="{{ route('admin.apps.flow', $app) }}" class="hover:text-indigo-600">Flow Designer</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $app->name }}</span>
@endsection
@section('content')
<div x-data="flowVisualDesigner(
    {!! htmlspecialchars(json_encode($app->flow_schema ?? []), ENT_QUOTES, 'UTF-8') !!},
    {!! htmlspecialchars(json_encode($roles->map(fn($r)=>['id'=>$r->id,'name'=>$r->name])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!},
    {!! htmlspecialchars(json_encode($optionSets->map(fn($o)=>['id'=>$o->id,'code'=>$o->code,'name_th'=>$o->name_th])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!},
    '{{ $app->slug }}'
)" class="flex flex-col h-[calc(100vh-9rem)]">

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h1 class="text-lg font-bold">{{ $app->name }} — Visual Flow</h1>
        <div class="flex items-center space-x-2">
            <span x-show="saveStatus" x-text="saveStatus"
                  :class="saveStatus === 'Saved!' ? 'text-green-600' : 'text-red-600'"
                  class="text-sm font-medium" x-cloak></span>
            <button @click="clearCanvas" class="btn-secondary text-sm">
                <i class="ti ti-trash mr-1"></i>Clear
            </button>
            <button @click="saveFlow" class="btn-primary text-sm">
                <i class="ti ti-device-floppy mr-1"></i>Save Flow
            </button>
        </div>
    </div>

    <div class="flex gap-3 flex-1 min-h-0">
        <!-- Left: Node Palette -->
        <div class="w-44 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-3 flex flex-col space-y-2 overflow-y-auto">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Node Types</p>
            <p class="text-xs text-gray-400">Drag to canvas</p>

            @foreach([
                ['type'=>'start',           'label'=>'Start',              'color'=>'bg-gray-500',   'icon'=>'ti-player-play'],
                ['type'=>'approval',        'label'=>'Approval Step',      'color'=>'bg-blue-500',   'icon'=>'ti-checkbox'],
                ['type'=>'end_approved',    'label'=>'End: Approved',      'color'=>'bg-green-500',  'icon'=>'ti-circle-check'],
                ['type'=>'end_rejected',    'label'=>'End: Rejected',      'color'=>'bg-red-500',    'icon'=>'ti-circle-x'],
                ['type'=>'return_revision', 'label'=>'Return for Revision','color'=>'bg-yellow-500', 'icon'=>'ti-corner-up-left'],
            ] as $nt)
            <div draggable="true"
                 @dragstart="onDragStart($event, '{{ $nt['type'] }}')"
                 class="palette-node cursor-grab select-none rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-2.5 text-center hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                <div class="w-8 h-8 {{ $nt['color'] }} text-white rounded-lg flex items-center justify-center mx-auto mb-1.5">
                    <i class="ti {{ $nt['icon'] }} text-sm"></i>
                </div>
                <p class="text-xs font-medium leading-tight">{{ $nt['label'] }}</p>
            </div>
            @endforeach

            <div class="pt-2 border-t border-gray-200 dark:border-gray-600 mt-2">
                <p class="text-xs text-gray-400 leading-relaxed">
                    Connect nodes by dragging output ports to input ports.<br><br>
                    <span class="text-green-600 font-medium">Top output</span> = Approve path<br>
                    <span class="text-red-600 font-medium">Bottom output</span> = Reject path
                </p>
            </div>
        </div>

        <!-- Center: Drawflow Canvas -->
        <div class="flex-1 min-w-0 relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 overflow-hidden">
            <div id="drawflow-canvas" x-ref="dfCanvas"
                 @drop="onDrop($event)"
                 @dragover.prevent
                 class="w-full h-full">
            </div>
        </div>

        <!-- Right: Node Properties -->
        <div class="w-64 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-300 dark:border-gray-600 p-4 overflow-y-auto"
             x-show="selectedNode" x-cloak>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">Node Properties</h3>
                <button @click="deselectNode" class="text-gray-400 hover:text-gray-600 p-1">
                    <i class="ti ti-x text-xs"></i>
                </button>
            </div>

            <template x-if="selectedNode">
                <div class="space-y-3">
                    <div class="px-3 py-2 rounded-lg text-xs font-semibold uppercase text-white text-center"
                         :class="{
                             'bg-gray-500': selectedNode.type === 'start',
                             'bg-blue-500': selectedNode.type === 'approval',
                             'bg-green-500': selectedNode.type === 'end_approved',
                             'bg-red-500': selectedNode.type === 'end_rejected',
                             'bg-yellow-500': selectedNode.type === 'return_revision',
                         }"
                         x-text="nodeTypeLabel(selectedNode.type)"></div>

                    <!-- Fields only for approval nodes -->
                    <template x-if="selectedNode.type === 'approval'">
                        <div class="space-y-3">
                            <div>
                                <label class="form-label text-xs">Step Name (TH)</label>
                                <input type="text" class="form-input text-sm" x-model="selectedNode.name_th"
                                       placeholder="ชื่อขั้นตอน">
                            </div>
                            <div>
                                <label class="form-label text-xs">Step Name (EN)</label>
                                <input type="text" class="form-input text-sm" x-model="selectedNode.name_en"
                                       placeholder="Step name">
                            </div>
                            <div>
                                <label class="form-label text-xs">Approver Source</label>
                                <select class="form-select text-sm" x-model="selectedNode.approver_source">
                                    <option value="role">By Role</option>
                                    <option value="specific_user">Specific User</option>
                                    <option value="option_set">Option Set</option>
                                </select>
                            </div>
                            <div x-show="selectedNode.approver_source === 'role' || !selectedNode.approver_source">
                                <label class="form-label text-xs">Role</label>
                                <select class="form-select text-sm" x-model="selectedNode.approver_role">
                                    <option value="">-- Select Role --</option>
                                    <template x-for="role in roles" :key="role.id">
                                        <option :value="role.name" x-text="role.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="selectedNode.approver_source === 'option_set'">
                                <label class="form-label text-xs">Option Set</label>
                                <select class="form-select text-sm" x-model="selectedNode.approver_option_set">
                                    <option value="">-- Select Option Set --</option>
                                    <template x-for="os in optionSets" :key="os.id">
                                        <option :value="os.code" x-text="os.name_th + ' (' + os.code + ')'"></option>
                                    </template>
                                </select>
                            </div>
                            <div x-show="selectedNode.approver_source === 'specific_user'">
                                <label class="form-label text-xs">User ID</label>
                                <input type="number" class="form-input text-sm" x-model.number="selectedNode.approver_user_id"
                                       placeholder="User ID">
                            </div>
                            <div>
                                <label class="form-label text-xs">Scope</label>
                                <select class="form-select text-sm" x-model="selectedNode.scope">
                                    <option value="own_factory">Own Factory</option>
                                    <option value="parent_factory">Parent Factory</option>
                                    <option value="any_factory">Any Factory</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">Action Type</label>
                                <select class="form-select text-sm" x-model="selectedNode.action_type">
                                    <option value="any_one">Any One Approver</option>
                                    <option value="all_must">All Must Approve</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">SLA (hours)</label>
                                <input type="number" class="form-input text-sm" x-model.number="selectedNode.sla_hours"
                                       min="0" placeholder="24">
                            </div>
                            <button @click="updateSelectedNode"
                                    class="w-full btn-primary text-sm py-1.5">
                                <i class="ti ti-check mr-1"></i>Update Node
                            </button>
                        </div>
                    </template>

                    <template x-if="selectedNode.type !== 'approval'">
                        <div class="text-xs text-gray-400 text-center py-4">
                            This node type has no configurable properties.
                        </div>
                    </template>

                    <button @click="deleteSelectedNode"
                            class="w-full btn-danger text-sm py-1.5 mt-2">
                        <i class="ti ti-trash mr-1"></i>Delete Node
                    </button>
                </div>
            </template>

            <div x-show="!selectedNode" class="text-xs text-gray-400 text-center py-8">
                <i class="ti ti-hand-click text-3xl block mb-2"></i>
                Click a node to edit its properties
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Drawflow custom node styles */
.drawflow-node.df-start      { background: #6b7280; border-color: #4b5563; }
.drawflow-node.df-approval   { background: #3b82f6; border-color: #2563eb; }
.drawflow-node.df-end-approved   { background: #22c55e; border-color: #16a34a; }
.drawflow-node.df-end-rejected   { background: #ef4444; border-color: #dc2626; }
.drawflow-node.df-return-revision { background: #f59e0b; border-color: #d97706; }

.drawflow-node { border-radius: 12px; min-width: 150px; }
.drawflow-node .drawflow_content_node { padding: 10px; }

.df-node-inner { color: #fff; }
.df-node-title { font-weight: 700; font-size: 12px; text-align: center; letter-spacing: 0.02em; }
.df-node-body  { margin-top: 6px; font-size: 11px; opacity: 0.9; }
.df-node-body div { margin-bottom: 2px; }
.df-node-icon  { text-align: center; font-size: 20px; margin-bottom: 4px; }

.df-output-labels {
    margin-top: 6px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.df-output-label {
    font-size: 10px;
    font-weight: 600;
    text-align: right;
    padding: 1px 4px;
    border-radius: 4px;
}
.df-output-label.approve { background: rgba(255,255,255,0.2); }
.df-output-label.reject  { background: rgba(0,0,0,0.15); }

/* Port colors */
.drawflow-node.df-approval .output.output_1 { background: #16a34a; }
.drawflow-node.df-approval .output.output_2 { background: #dc2626; }

/* Connection colors */
.drawflow svg .connection.selected path { stroke: #6366f1; }

#drawflow-canvas {
    background-color: #f9fafb;
    background-image: radial-gradient(circle, #d1d5db 1px, transparent 1px);
    background-size: 20px 20px;
}
</style>
@endpush

@push('scripts')
<script>
function flowVisualDesigner(initialSchema, roles, optionSets, appSlug) {
    return {
        editor: null,
        selectedNodeId: null,
        selectedNode: null,
        roles,
        optionSets,
        appSlug,
        saveStatus: '',
        dragNodeType: null,
        _nodeCounter: 0,

        init() {
            this.$nextTick(() => {
                this.initDrawflow();
                // Only import if df_data is a valid Drawflow export object (not [] or null)
                if (initialSchema && initialSchema.df_data &&
                    typeof initialSchema.df_data === 'object' &&
                    !Array.isArray(initialSchema.df_data) &&
                    initialSchema.df_data.drawflow) {
                    try { this.editor.import(initialSchema.df_data); } catch(e) { console.warn('Could not import df_data:', e); }
                }
            });
        },

        initDrawflow() {
            const container = this.$refs.dfCanvas;
            this.editor = new Drawflow(container);
            this.editor.reroute = true;
            this.editor.reroute_fix_curvature = true;
            this.editor.editor_mode = 'edit';
            this.editor.start();

            this.editor.on('nodeSelected', (id) => {
                const node = this.editor.getNodeFromId(id);
                this.selectedNodeId = id;
                this.selectedNode   = { ...node.data };
            });

            this.editor.on('nodeUnselected', () => {
                this.selectedNodeId = null;
                this.selectedNode   = null;
            });

            this.editor.on('nodeRemoved', () => {
                this.selectedNodeId = null;
                this.selectedNode   = null;
            });
        },

        onDragStart(event, type) {
            event.dataTransfer.setData('node_type', type);
            event.dataTransfer.effectAllowed = 'copy';
        },

        onDrop(event) {
            event.preventDefault();
            const type = event.dataTransfer.getData('node_type');
            if (!type || !this.editor) return;

            const rect   = this.$refs.dfCanvas.getBoundingClientRect();
            const posX   = (event.clientX - rect.left - this.editor.canvas_x) / this.editor.zoom;
            const posY   = (event.clientY - rect.top  - this.editor.canvas_y) / this.editor.zoom;

            this.addNode(type, posX, posY);
        },

        addNode(type, posX = 200, posY = 200) {
            const nodeId = `${type}_${Date.now()}_${++this._nodeCounter}`;
            const data = {
                id: nodeId, type,
                name_th: '', name_en: '',
                approver_source: 'role', approver_role: this.roles[0]?.name || '',
                approver_user_id: null, approver_option_set: null,
                scope: 'own_factory', action_type: 'any_one', sla_hours: 24,
            };

            const configs = {
                start:           { inputs: 0, outputs: 1, css: 'df-start' },
                approval:        { inputs: 1, outputs: 2, css: 'df-approval' },
                end_approved:    { inputs: 1, outputs: 0, css: 'df-end-approved' },
                end_rejected:    { inputs: 1, outputs: 0, css: 'df-end-rejected' },
                return_revision: { inputs: 1, outputs: 1, css: 'df-return-revision' },
            };
            const cfg = configs[type];
            if (!cfg) return;

            this.editor.addNode(type, cfg.inputs, cfg.outputs, posX, posY, cfg.css, data, this.buildNodeHtml(type, data));
        },

        buildNodeHtml(type, data) {
            const labels = {
                start:           '<div class="df-node-icon">▶</div><div class="df-node-title">Start</div>',
                end_approved:    '<div class="df-node-icon">✅</div><div class="df-node-title">End: Approved</div>',
                end_rejected:    '<div class="df-node-icon">❌</div><div class="df-node-title">End: Rejected</div>',
                return_revision: '<div class="df-node-icon">↩</div><div class="df-node-title">Return for Revision</div>',
                approval: `
                    <div class="df-node-title">✅ Approval Step</div>
                    <div class="df-node-body">
                        <div class="df-step-name">${data.name_th || 'Unnamed'}</div>
                        <div class="df-approver-info">${data.approver_source || 'role'}: ${data.approver_role || '-'}</div>
                        <div class="df-sla-info">SLA: ${data.sla_hours || '-'}h</div>
                    </div>
                    <div class="df-output-labels">
                        <div class="df-output-label approve">✓ Approve</div>
                        <div class="df-output-label reject">✗ Reject</div>
                    </div>`,
            };
            return `<div class="df-node-inner">${labels[type] || ''}</div>`;
        },

        updateSelectedNode() {
            if (!this.selectedNodeId || !this.selectedNode) return;
            const data = { ...this.selectedNode };
            this.editor.updateNodeDataFromId(this.selectedNodeId, data);

            // Update displayed HTML
            const el = document.querySelector(`#node-${this.selectedNodeId} .drawflow_content_node`);
            if (el && data.type === 'approval') {
                const nameEl     = el.querySelector('.df-step-name');
                const approverEl = el.querySelector('.df-approver-info');
                const slaEl      = el.querySelector('.df-sla-info');
                if (nameEl)     nameEl.textContent     = data.name_th || 'Unnamed';
                if (approverEl) approverEl.textContent = `${data.approver_source || 'role'}: ${data.approver_role || data.approver_option_set || '-'}`;
                if (slaEl)      slaEl.textContent      = `SLA: ${data.sla_hours || '-'}h`;
            }
            this.saveStatus = '';
        },

        deleteSelectedNode() {
            if (!this.selectedNodeId) return;
            this.editor.removeNodeId(`node-${this.selectedNodeId}`);
            this.selectedNodeId = null;
            this.selectedNode   = null;
        },

        deselectNode() {
            this.selectedNodeId = null;
            this.selectedNode   = null;
        },

        clearCanvas() {
            if (!confirm('Clear all nodes?')) return;
            this.editor.clear();
            this.selectedNodeId = null;
            this.selectedNode   = null;
        },

        async saveFlow() {
            this.saveStatus = 'Saving...';
            const dfData   = this.editor.export();
            const schema   = this.drawflowToSchema(dfData);
            schema.df_data = dfData;

            try {
                const res = await fetch(`/admin/apps/${this.appSlug}/save-flow`, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ flow_schema: schema }),
                });
                this.saveStatus = res.ok ? 'Saved!' : 'Error saving';
                setTimeout(() => { this.saveStatus = ''; }, 3000);
            } catch (e) {
                this.saveStatus = 'Network error';
            }
        },

        drawflowToSchema(dfData) {
            const home  = dfData?.drawflow?.Home?.data || {};
            const nodes = [];
            const edges = [];
            const idMap = {}; // drawflow numeric id → our string id

            for (const [dfId, dfNode] of Object.entries(home)) {
                const d  = dfNode.data || {};
                const id = d.id || `node_${dfId}`;
                idMap[dfId] = id;
                nodes.push({
                    id,
                    type:                 dfNode.name,
                    name_th:              d.name_th              || '',
                    name_en:              d.name_en              || '',
                    approver_source:      d.approver_source      || 'role',
                    approver_role:        d.approver_role        || '',
                    approver_user_id:     d.approver_user_id     || null,
                    approver_option_set:  d.approver_option_set  || null,
                    scope:                d.scope                || 'own_factory',
                    action_type:          d.action_type          || 'any_one',
                    sla_hours:            d.sla_hours            || null,
                    pos: { x: dfNode.pos_x, y: dfNode.pos_y },
                });
            }

            // Second pass: edges from output connections
            for (const [dfId, dfNode] of Object.entries(home)) {
                const fromId   = idMap[dfId];
                const nodeName = dfNode.name;

                for (const [portKey, port] of Object.entries(dfNode.outputs || {})) {
                    let label = null;
                    if (nodeName === 'approval') {
                        label = portKey === 'output_1' ? 'approve' : 'reject';
                    }
                    for (const conn of port.connections || []) {
                        const toId = idMap[conn.node];
                        if (toId) edges.push({ from: fromId, to: toId, label });
                    }
                }
            }

            return { nodes, edges };
        },

        nodeTypeLabel(type) {
            const labels = {
                start: 'Start', approval: 'Approval Step',
                end_approved: 'End: Approved', end_rejected: 'End: Rejected',
                return_revision: 'Return for Revision',
            };
            return labels[type] || type;
        },
    };
}
</script>
@endpush
