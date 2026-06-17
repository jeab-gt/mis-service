@extends('layouts.app')
@section('title', 'Flow Designer — ' . $flow->name)
@section('breadcrumb')
<a href="{{ route('admin.flows.index') }}" class="hover:text-indigo-600">Flow Library</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>{{ $flow->name }}</span>
@endsection

@section('content')
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
    {!! htmlspecialchars(json_encode($stepForms->map(fn($t)=>['id'=>$t->id,'name'=>$t->name,'category'=>$t->category])->values()->toArray()), ENT_QUOTES, 'UTF-8') !!},
    '{{ route('admin.flows.save', $flow) }}'
)" class="flex flex-col h-[calc(100vh-9rem)]">

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
        <h1 class="text-lg font-bold">{{ $flow->name }} — Flow Designer</h1>
        <div class="flex items-center space-x-2">
            <span x-show="saveStatus" x-text="saveStatus"
                  :class="saveStatus === 'Saved!' ? 'text-green-600' : 'text-red-600'"
                  class="text-sm font-medium" x-cloak></span>
            <button @click="clearCanvas" class="btn-secondary text-sm"><i class="ti ti-trash mr-1"></i>Clear</button>
            <button @click="saveFlow"   class="btn-primary text-sm"><i class="ti ti-device-floppy mr-1"></i>Save</button>
        </div>
    </div>

    <div class="flex gap-3 flex-1 min-h-0">
        <!-- Left: Node Palette -->
        <div class="w-44 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-3 flex flex-col space-y-2 overflow-y-auto">
            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Node Types</p>
            @foreach([
                ['type'=>'start',           'label'=>'Start',               'color'=>'bg-gray-500',   'icon'=>'ti-player-play'],
                ['type'=>'approval',        'label'=>'Approval Step',       'color'=>'bg-blue-500',   'icon'=>'ti-checkbox'],
                ['type'=>'end_approved',    'label'=>'End: Approved',       'color'=>'bg-green-500',  'icon'=>'ti-circle-check'],
                ['type'=>'end_rejected',    'label'=>'End: Rejected',       'color'=>'bg-red-500',    'icon'=>'ti-circle-x'],
                ['type'=>'return_revision', 'label'=>'Return for Revision', 'color'=>'bg-yellow-500', 'icon'=>'ti-corner-up-left'],
            ] as $nt)
            <div @click="addNode('{{ $nt['type'] }}')"
                 class="cursor-pointer select-none rounded-xl border-2 border-dashed border-gray-300 dark:border-gray-600 p-2.5 text-center hover:border-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                <div class="w-8 h-8 {{ $nt['color'] }} text-white rounded-lg flex items-center justify-center mx-auto mb-1.5">
                    <i class="ti {{ $nt['icon'] }} text-sm"></i>
                </div>
                <p class="text-xs font-medium leading-tight">{{ $nt['label'] }}</p>
            </div>
            @endforeach
        </div>

        <!-- Center: Canvas (SVG-based visual) -->
        <div class="flex-1 min-w-0 relative bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <svg x-ref="svg" class="absolute inset-0 w-full h-full pointer-events-none z-0">
                <defs>
                    <marker id="arrow" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                        <polygon points="0 0, 10 3.5, 0 7" fill="#6366f1"/>
                    </marker>
                </defs>
                <template x-for="(edge, ei) in flow.edges" :key="ei">
                    <g>
                        <line :x1="getNodeCenter(edge.from_node_id).x" :y1="getNodeCenter(edge.from_node_id).y"
                              :x2="getNodeCenter(edge.to_node_id).x"   :y2="getNodeCenter(edge.to_node_id).y"
                              stroke="#6366f1" stroke-width="2" marker-end="url(#arrow)"/>
                        <text
                            :x="(getNodeCenter(edge.from_node_id).x + getNodeCenter(edge.to_node_id).x)/2"
                            :y="(getNodeCenter(edge.from_node_id).y + getNodeCenter(edge.to_node_id).y)/2 - 6"
                            fill="#6366f1" font-size="10" text-anchor="middle"
                            x-text="edge.label ?? ''"></text>
                    </g>
                </template>
            </svg>

            <div class="absolute inset-0 p-4 overflow-auto" x-ref="canvas">
                <template x-for="(node, ni) in flow.nodes" :key="node.node_id">
                    <div class="absolute cursor-move select-none"
                         :style="`left:${node.pos_x}px;top:${node.pos_y}px;`"
                         @mousedown="startDrag($event, ni)">
                        <div class="relative rounded-xl shadow-md border-2 p-3 w-36 text-center"
                             :class="getNodeClass(node.type)"
                             @click.stop="selectNode(ni)">
                            <div class="text-xs font-bold uppercase mb-1" x-text="node.type.replace('_',' ')"></div>
                            <div class="text-sm font-medium leading-tight" x-text="node.name_th || node.name_en || node.node_id"></div>
                            <div x-show="node.sla_hours" class="text-xs text-gray-400 mt-1" x-text="'SLA: ' + node.sla_hours + 'h'" x-cloak></div>
                            <!-- Delete -->
                            <button @click.stop="removeNode(ni)"
                                    class="absolute -top-2 -right-2 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 hover:opacity-100 transition-opacity">
                                <i class="ti ti-x" style="font-size:10px"></i>
                            </button>
                        </div>
                        <!-- Connect buttons -->
                        <div class="flex justify-center mt-1 space-x-1">
                            <button @click.stop="startConnect(node.node_id, 'approve')"
                                    class="text-xs px-2 py-0.5 bg-green-100 text-green-700 rounded hover:bg-green-200">→ approve</button>
                            <button @click.stop="startConnect(node.node_id, 'reject')"
                                    class="text-xs px-2 py-0.5 bg-red-100 text-red-700 rounded hover:bg-red-200">→ reject</button>
                            <button @click.stop="startConnect(node.node_id, null)"
                                    class="text-xs px-2 py-0.5 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">→</button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Connect mode banner -->
            <div x-show="connectFrom" x-cloak
                 class="absolute top-2 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-xs px-4 py-2 rounded-full shadow">
                คลิก Node ปลายทาง… <button @click="connectFrom=null;connectLabel=null" class="ml-2 underline">ยกเลิก</button>
            </div>
        </div>

        <!-- Right: Node Properties -->
        <div class="w-64 flex-shrink-0 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 overflow-y-auto"
             x-show="selectedIdx !== null" x-cloak>
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-sm">Node Properties</h3>
                <button @click="selectedIdx=null" class="text-gray-400 hover:text-gray-600"><i class="ti ti-x text-sm"></i></button>
            </div>
            <template x-if="selectedIdx !== null && flow.nodes[selectedIdx]">
                <div class="space-y-3 text-sm">
                    <div>
                        <label class="form-label text-xs">Node ID</label>
                        <input type="text" x-model="flow.nodes[selectedIdx].node_id" class="form-input text-sm" placeholder="unique_id">
                    </div>
                    <div>
                        <label class="form-label text-xs">Name TH</label>
                        <input type="text" x-model="flow.nodes[selectedIdx].name_th" class="form-input text-sm">
                    </div>
                    <div>
                        <label class="form-label text-xs">Name EN</label>
                        <input type="text" x-model="flow.nodes[selectedIdx].name_en" class="form-input text-sm">
                    </div>

                    <template x-if="flow.nodes[selectedIdx].type === 'approval'">
                        <div class="space-y-3">
                            <div>
                                <label class="form-label text-xs">Approver Source</label>
                                <select x-model="flow.nodes[selectedIdx].approver_source" class="form-select text-sm">
                                    <option value="role">Role</option>
                                    <option value="specific_user">Specific User</option>
                                    <option value="option_set">Option Set</option>
                                </select>
                            </div>
                            <template x-if="flow.nodes[selectedIdx].approver_source === 'role'">
                                <div>
                                    <label class="form-label text-xs">Role</label>
                                    <select x-model="flow.nodes[selectedIdx].approver_role_id" class="form-select text-sm">
                                        <option value="">-- เลือก Role --</option>
                                        <template x-for="r in roles" :key="r.id">
                                            <option :value="r.id" x-text="r.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                            <template x-if="flow.nodes[selectedIdx].approver_source === 'option_set'">
                                <div>
                                    <label class="form-label text-xs">Option Set Code</label>
                                    <input type="text" x-model="flow.nodes[selectedIdx].approver_option_set_code" class="form-input text-sm">
                                </div>
                            </template>
                            <div>
                                <label class="form-label text-xs">Scope</label>
                                <select x-model="flow.nodes[selectedIdx].scope" class="form-select text-sm">
                                    <option value="own_factory">Own Factory</option>
                                    <option value="parent_factory">Parent Factory</option>
                                    <option value="any_factory">Any Factory</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">Action Type</label>
                                <select x-model="flow.nodes[selectedIdx].action_type" class="form-select text-sm">
                                    <option value="any_one">Any One</option>
                                    <option value="all_must">All Must</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label text-xs">SLA (hours)</label>
                                <input type="number" x-model.number="flow.nodes[selectedIdx].sla_hours" class="form-input text-sm" min="0">
                            </div>
                            <div>
                                <label class="form-label text-xs">Step Form Template</label>
                                <select x-model.number="flow.nodes[selectedIdx].step_form_template_id" class="form-select text-sm">
                                    <option :value="null">-- ไม่มี --</option>
                                    <template x-for="f in stepForms" :key="f.id">
                                        <option :value="f.id" x-text="f.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-400 mt-1" x-show="flow.nodes[selectedIdx].step_form_template_id">
                                    Approver กรอก form นี้ขณะ approve
                                </p>
                            </div>
                        </div>
                    </template>

                    <!-- Edges from this node -->
                    <div class="pt-2 border-t border-gray-100 dark:border-gray-700">
                        <p class="text-xs font-semibold text-gray-500 mb-1">Edges from this node</p>
                        <template x-for="(edge, ei) in edgesFrom(flow.nodes[selectedIdx].node_id)" :key="ei">
                            <div class="flex items-center justify-between text-xs bg-gray-50 dark:bg-gray-750 rounded px-2 py-1 mb-1">
                                <span x-text="(edge.label ?? '—') + ' → ' + edge.to_node_id"></span>
                                <button @click="removeEdge(edge)" class="text-red-400 hover:text-red-600"><i class="ti ti-x"></i></button>
                            </div>
                        </template>
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
        flow: { nodes: initialFlow.nodes || [], edges: initialFlow.edges || [] },
        roles,
        stepForms,
        selectedIdx: null,
        connectFrom: null,
        connectLabel: null,
        saveStatus: '',
        dragging: null,
        dragOffset: { x: 0, y: 0 },

        nodeColors: {
            start: 'bg-gray-200 border-gray-400 text-gray-700',
            approval: 'bg-blue-50 border-blue-400 text-blue-700',
            end_approved: 'bg-green-50 border-green-400 text-green-700',
            end_rejected: 'bg-red-50 border-red-400 text-red-700',
            return_revision: 'bg-yellow-50 border-yellow-400 text-yellow-700',
        },

        getNodeClass(type) {
            return this.nodeColors[type] || 'bg-gray-100 border-gray-300 text-gray-700';
        },

        addNode(type) {
            const id = type + '_' + Date.now().toString(36);
            this.flow.nodes.push({
                node_id: id, type,
                name_th: '', name_en: '',
                approver_source: 'role',
                approver_role_id: null,
                approver_option_set_code: null,
                scope: 'own_factory',
                action_type: 'any_one',
                sla_hours: null,
                step_form_template_id: null,
                pos_x: 100 + Math.random()*200,
                pos_y: 100 + Math.random()*200,
            });
        },

        selectNode(idx) {
            if (this.connectFrom !== null) {
                const toId = this.flow.nodes[idx].node_id;
                if (toId !== this.connectFrom) {
                    this.flow.edges.push({
                        from_node_id: this.connectFrom,
                        to_node_id: toId,
                        label: this.connectLabel,
                    });
                }
                this.connectFrom = null;
                this.connectLabel = null;
            } else {
                this.selectedIdx = idx;
            }
        },

        removeNode(idx) {
            const id = this.flow.nodes[idx].node_id;
            this.flow.nodes.splice(idx, 1);
            this.flow.edges = this.flow.edges.filter(e => e.from_node_id !== id && e.to_node_id !== id);
            this.selectedIdx = null;
        },

        startConnect(fromId, label) {
            this.connectFrom  = fromId;
            this.connectLabel = label;
        },

        removeEdge(edge) {
            this.flow.edges = this.flow.edges.filter(e => e !== edge);
        },

        edgesFrom(nodeId) {
            return this.flow.edges.filter(e => e.from_node_id === nodeId);
        },

        getNodeCenter(nodeId) {
            const node = this.flow.nodes.find(n => n.node_id === nodeId);
            if (!node) return { x: 0, y: 0 };
            return { x: node.pos_x + 72, y: node.pos_y + 40 };
        },

        clearCanvas() {
            if (confirm('ล้าง canvas ทั้งหมด?')) {
                this.flow = { nodes: [], edges: [] };
                this.selectedIdx = null;
            }
        },

        startDrag(event, idx) {
            event.preventDefault();
            this.dragging = idx;
            const node = this.flow.nodes[idx];
            this.dragOffset.x = event.clientX - node.pos_x;
            this.dragOffset.y = event.clientY - node.pos_y;
            const onMove = (e) => {
                if (this.dragging === null) return;
                this.flow.nodes[this.dragging].pos_x = Math.max(0, e.clientX - this.dragOffset.x);
                this.flow.nodes[this.dragging].pos_y = Math.max(0, e.clientY - this.dragOffset.y);
            };
            const onUp = () => {
                this.dragging = null;
                window.removeEventListener('mousemove', onMove);
                window.removeEventListener('mouseup', onUp);
            };
            window.addEventListener('mousemove', onMove);
            window.addEventListener('mouseup', onUp);
        },

        async saveFlow() {
            this.saveStatus = 'Saving…';
            try {
                const res = await fetch(saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify(this.flow),
                });
                this.saveStatus = res.ok ? 'Saved!' : 'Error!';
            } catch {
                this.saveStatus = 'Error!';
            }
            setTimeout(() => this.saveStatus = '', 3000);
        },
    };
}
</script>
@endpush
