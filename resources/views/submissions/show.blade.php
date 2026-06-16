@extends('layouts.app')
@section('title', 'Request #' . $submission->id)

@section('breadcrumb')
<a href="{{ route('submissions.index') }}" class="hover:text-indigo-600">{{ __('menu.all_requests') }}</a>
<i class="ti ti-chevron-right text-xs"></i>
<span>#{{ $submission->id }}</span>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Left: details -->
    <div class="lg:col-span-2 space-y-4">
        <!-- Info card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h1 class="text-xl font-bold">{{ $submission->app->name }} <span class="text-gray-400 font-normal">#{{ $submission->id }}</span></h1>
                    <p class="text-sm text-gray-500 mt-1">{{ app()->getLocale() === 'th' ? 'ผู้ส่ง: ' : 'Submitter: ' }}{{ $submission->submitter->name }}</p>
                </div>
                <span class="status-badge status-{{ $submission->status }} text-sm px-3 py-1">
                    {{ ucfirst(str_replace('_', ' ', $submission->status)) }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($submission->app->form_schema['fields'] ?? [] as $field)
                @php
                    $labelKey = app()->getLocale() === 'th' ? 'label_th' : 'label_en';
                    $label = $field[$labelKey] ?? $field['label_th'] ?? '';
                    $value = $submission->form_data[$field['id']] ?? '-';
                    $colClass = ($field['width'] ?? 'full') === 'full' ? 'md:col-span-2' : '';
                @endphp
                <div class="{{ $colClass }}">
                    <p class="text-xs text-gray-500 mb-1">{{ $label }}</p>
                    @if($field['type'] === 'file' && $value !== '-')
                        <a href="{{ Storage::url($value) }}" target="_blank" class="text-indigo-500 hover:underline text-sm">
                            <i class="ti ti-paperclip mr-1"></i>{{ __('common.view_file') }}
                        </a>
                    @else
                        <p class="font-medium text-gray-800 dark:text-gray-200">{{ $value }}</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- Approval timeline -->
        @php
            $flowSchema  = $submission->app->flow_schema ?? [];
            $isGraphFlow = isset($flowSchema['nodes']) && !empty($flowSchema['nodes']);
            $graphNodes  = collect($flowSchema['nodes'] ?? []);
            $graphEdges  = collect($flowSchema['edges'] ?? []);
            // Build ordered list of approval nodes for timeline display
            $timelineNodes = $isGraphFlow
                ? $graphNodes->filter(fn($n) => $n['type'] === 'approval')->values()
                : collect();
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'ขั้นตอนอนุมัติ' : 'Approval Timeline' }}</h3>
            <div class="space-y-4">
                @if($isGraphFlow)
                    @forelse($timelineNodes as $i => $node)
                    @php
                        $nodeAction    = $submission->approvalActions->where('node_id', $node['id'])->last();
                        $isCurrentNode = $submission->current_step === $node['id'];
                        $isDone        = $nodeAction !== null;
                    @endphp
                    <div class="flex space-x-3">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                                {{ $isDone && $nodeAction->action === 'approve' ? 'bg-green-100 text-green-600' :
                                   ($isDone && $nodeAction->action === 'reject' ? 'bg-red-100 text-red-600' :
                                   ($isDone ? 'bg-orange-100 text-orange-600' :
                                   ($isCurrentNode ? 'bg-yellow-100 text-yellow-600 animate-pulse' : 'bg-gray-100 text-gray-400'))) }}">
                                @if($isDone && $nodeAction->action === 'approve') <i class="ti ti-check"></i>
                                @elseif($isDone && $nodeAction->action === 'reject') <i class="ti ti-x"></i>
                                @elseif($isDone) <i class="ti ti-refresh"></i>
                                @else {{ $i + 1 }}
                                @endif
                            </div>
                            @if($i < $timelineNodes->count() - 1)<div class="w-0.5 h-6 bg-gray-200 dark:bg-gray-600 mt-1"></div>@endif
                        </div>
                        <div class="flex-1 pb-4">
                            <p class="font-medium text-sm">{{ $node['label'] ?? 'Approval' }}</p>
                            <p class="text-xs text-gray-400">{{ $node['approver_role'] ?? '' }}</p>
                            @if($isDone)
                            <div class="mt-1 text-xs text-gray-500">
                                <span class="font-medium">{{ $nodeAction->actor->name }}</span>
                                — {{ $nodeAction->acted_at->format('d/m/Y H:i') }}
                                @if($nodeAction->comment)
                                <p class="mt-0.5 italic">"{{ $nodeAction->comment }}"</p>
                                @endif
                            </div>
                            @elseif($isCurrentNode)
                            <p class="mt-1 text-xs text-yellow-600">{{ app()->getLocale() === 'th' ? 'รออนุมัติ' : 'Pending' }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-400 text-sm">{{ app()->getLocale() === 'th' ? 'ไม่มีขั้นตอนการอนุมัติ' : 'No approval steps defined.' }}</p>
                    @endforelse
                @else
                    @foreach($submission->app->approvalSteps as $step)
                    @php
                        $stepAction    = $submission->approvalActions->where('step_id', $step->id)->first();
                        $isCurrentStep = $submission->current_step == $step->step_order;
                        $isDone        = $stepAction !== null;
                    @endphp
                    <div class="flex space-x-3">
                        <div class="flex flex-col items-center">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                                {{ $isDone && $stepAction->action === 'approve' ? 'bg-green-100 text-green-600' :
                                   ($isDone && $stepAction->action === 'reject' ? 'bg-red-100 text-red-600' :
                                   ($isCurrentStep ? 'bg-yellow-100 text-yellow-600 animate-pulse' : 'bg-gray-100 text-gray-400')) }}">
                                @if($isDone && $stepAction->action === 'approve') <i class="ti ti-check"></i>
                                @elseif($isDone && $stepAction->action === 'reject') <i class="ti ti-x"></i>
                                @else {{ $step->step_order }}
                                @endif
                            </div>
                            @if(!$loop->last)<div class="w-0.5 h-6 bg-gray-200 dark:bg-gray-600 mt-1"></div>@endif
                        </div>
                        <div class="flex-1 pb-4">
                            <p class="font-medium text-sm">{{ $step->display_name }}</p>
                            <p class="text-xs text-gray-400">{{ $step->approverRole->name }}</p>
                            @if($isDone)
                            <div class="mt-1 text-xs text-gray-500">
                                <span class="font-medium">{{ $stepAction->actor->name }}</span>
                                — {{ $stepAction->acted_at->format('d/m/Y H:i') }}
                                @if($stepAction->comment)
                                <p class="mt-0.5 italic">"{{ $stepAction->comment }}"</p>
                                @endif
                            </div>
                            @elseif($isCurrentStep)
                            <p class="mt-1 text-xs text-yellow-600">{{ app()->getLocale() === 'th' ? 'รออนุมัติ' : 'Pending' }}</p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Daily Logs -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
            <h3 class="font-semibold mb-4">{{ app()->getLocale() === 'th' ? 'บันทึกประจำวัน' : 'Daily Logs' }}</h3>
            @forelse($submission->dailyLogs->sortByDesc('log_date') as $log)
            <div class="flex items-start space-x-3 py-3 border-b border-gray-100 dark:border-gray-700 last:border-0">
                <div class="w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center flex-shrink-0">
                    <span class="text-xs font-bold text-gray-500">{{ $log->progress_pct }}%</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-2">
                        <p class="text-sm font-medium">{{ $log->user->name }}</p>
                        <span class="text-xs text-gray-400">{{ $log->log_date->format('d/m/Y') }}</span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5">{{ $log->detail }}</p>
                    <div class="mt-1 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1">
                        <div class="bg-indigo-500 h-1 rounded-full" style="width:{{ $log->progress_pct }}%"></div>
                    </div>
                </div>
            </div>
            @empty
            <p class="text-gray-400 text-sm">{{ __('common.no_data') }}</p>
            @endforelse

            <!-- Add log form: only show to current assignee -->
            @if(!in_array($submission->status, ['approved','rejected','closed']) && $submission->latestAssignment?->assignee_id == auth()->id())
            <form method="POST" action="{{ route('submissions.log', $submission) }}" class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                @csrf
                <h4 class="text-sm font-semibold mb-3">{{ app()->getLocale() === 'th' ? 'เพิ่มบันทึก' : 'Add Log' }}</h4>
                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="form-label text-xs">{{ app()->getLocale() === 'th' ? 'วันที่' : 'Date' }}</label>
                        <input type="date" name="log_date" value="{{ now()->toDateString() }}" class="form-input text-sm" required>
                    </div>
                    <div>
                        <label class="form-label text-xs">{{ app()->getLocale() === 'th' ? 'ความคืบหน้า %' : 'Progress %' }}</label>
                        <input type="number" name="progress_pct" min="0" max="100" value="0" class="form-input text-sm" required>
                    </div>
                </div>
                <textarea name="detail" rows="2" class="form-input text-sm mb-2"
                          placeholder="{{ app()->getLocale() === 'th' ? 'รายละเอียด...' : 'Detail...' }}" required></textarea>
                <button type="submit" class="btn-primary text-sm">{{ __('common.save') }}</button>
            </form>
            @endif
        </div>
    </div>

    <!-- Right: actions -->
    @php
        $canApproveStep     = false;
        $currentNodeForDisplay = null;
        $hasReturnRevisionEdge = false;

        if ($isGraphFlow) {
            $currentNode = $graphNodes->firstWhere('id', $submission->current_step);
            $currentNodeForDisplay = $currentNode;
            if ($currentNode && $currentNode['type'] === 'approval' && in_array($submission->status, ['submitted', 'in_review'])) {
                $me    = auth()->user();
                $role  = $currentNode['approver_role'] ?? '';
                $scope = $currentNode['scope'] ?? 'own_factory';
                if ($me->hasRole('super_admin')) {
                    $canApproveStep = true;
                } else {
                    $hasRole = $role && $me->hasRole($role);
                    $inScope = $me->is_parent_factory ? true : match($scope) {
                        'own_factory'    => $me->factory_id == $submission->factory_id,
                        'parent_factory' => false,
                        'any_factory'    => true,
                        default          => false,
                    };
                    $canApproveStep = $hasRole && $inScope;
                }
                // Check if this approval node has a return_revision edge (output_3 or labeled 'return')
                $targetEdges = $graphEdges->filter(fn($e) => $e['from'] === $currentNode['id']);
                foreach ($targetEdges as $edge) {
                    $targetNode = $graphNodes->firstWhere('id', $edge['to'] ?? '');
                    if ($targetNode && $targetNode['type'] === 'return_revision') {
                        $hasReturnRevisionEdge = true;
                        break;
                    }
                }
            }
        } else {
            $currentStep = $submission->app->approvalSteps->firstWhere('step_order', (int) $submission->current_step);
            if ($currentStep && in_array($submission->status, ['submitted', 'in_review'])) {
                $me      = auth()->user();
                $role    = $currentStep->approverRole?->name;
                $scope   = $currentStep->scope ?? 'own_factory';
                $hasRole = $role && $me->hasRole($role);
                $inScope = $me->is_parent_factory ? true : match($scope) {
                    'own_factory'    => $me->factory_id == $submission->factory_id,
                    'parent_factory' => false,
                    'any_factory'    => true,
                    default          => false,
                };
                $canApproveStep = $hasRole && $inScope;
            }
        }
    @endphp
    <div class="space-y-4">
        <!-- Approve/Reject -->
        @if($canApproveStep)
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-3">{{ app()->getLocale() === 'th' ? 'ดำเนินการ' : 'Action' }}</h3>
            @if($currentNodeForDisplay)
            <p class="text-xs text-gray-500 mb-3">{{ $currentNodeForDisplay['label'] ?? '' }}
                @if(!empty($currentNodeForDisplay['approver_role']))
                <span class="ml-1 text-indigo-500">({{ $currentNodeForDisplay['approver_role'] }})</span>
                @endif
            </p>
            @endif
            <form method="POST" action="{{ route('submissions.approve', $submission) }}">
                @csrf
                <textarea name="comment" rows="2" class="form-input text-sm mb-3"
                          placeholder="{{ app()->getLocale() === 'th' ? 'ความเห็น...' : 'Comment...' }}"></textarea>
                <div class="flex space-x-2">
                    <button type="submit" name="action" value="approve"
                            class="flex-1 btn-success text-sm flex items-center justify-center space-x-1">
                        <i class="ti ti-check"></i><span>{{ __('common.approve') }}</span>
                    </button>
                    <button type="submit" name="action" value="reject"
                            class="flex-1 btn-danger text-sm flex items-center justify-center space-x-1">
                        <i class="ti ti-x"></i><span>{{ __('common.reject') }}</span>
                    </button>
                </div>
                @if($hasReturnRevisionEdge)
                <button type="submit" name="action" value="return_revision"
                        class="mt-2 w-full btn-warning text-sm flex items-center justify-center space-x-1">
                    <i class="ti ti-refresh"></i><span>{{ app()->getLocale() === 'th' ? 'ส่งกลับแก้ไข' : 'Return for Revision' }}</span>
                </button>
                @endif
            </form>
        </div>
        @endif

        <!-- Assign -->
        @if(!in_array($submission->status, ['approved','rejected','closed']))
        @can('submission.assign')
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-3">{{ app()->getLocale() === 'th' ? 'มอบหมายงาน' : 'Assign' }}</h3>
            <form method="POST" action="{{ route('submissions.assign', $submission) }}">
                @csrf
                <select name="assignee_id" class="form-select text-sm mb-2" required>
                    <option value="">-- {{ app()->getLocale() === 'th' ? 'เลือกผู้รับผิดชอบ' : 'Select Assignee' }} --</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}" {{ $submission->latestAssignment?->assignee_id == $s->id ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                    @endforeach
                </select>
                <input type="date" name="due_date" value="{{ $submission->latestAssignment?->due_date?->format('Y-m-d') }}" class="form-input text-sm mb-2">
                <button type="submit" class="btn-primary text-sm w-full">{{ app()->getLocale() === 'th' ? 'มอบหมาย' : 'Assign' }}</button>
            </form>
        </div>
        @endcan
        @endif

        <!-- Resubmit form: shown to submitter when returned for revision -->
        @if($submission->status === 'returned' && $submission->submitter_id === auth()->id())
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-amber-300 dark:border-amber-600 p-5">
            <h3 class="font-semibold mb-1 text-amber-700 dark:text-amber-400 flex items-center space-x-1">
                <i class="ti ti-refresh"></i>
                <span>{{ app()->getLocale() === 'th' ? 'แก้ไขและส่งใหม่' : 'Revise & Resubmit' }}</span>
            </h3>
            <p class="text-xs text-gray-500 mb-3">{{ app()->getLocale() === 'th' ? 'แก้ไขข้อมูลแล้วกด "ส่งใหม่"' : 'Update the information and click Resubmit.' }}</p>
            <form method="POST" action="{{ route('submissions.resubmit', $submission) }}">
                @csrf
                @foreach($submission->app->form_schema['fields'] ?? [] as $field)
                @php
                    $labelKey = app()->getLocale() === 'th' ? 'label_th' : 'label_en';
                    $label        = $field[$labelKey] ?? $field['label_th'] ?? '';
                    $currentValue = $submission->form_data[$field['id']] ?? '';
                    $inputName    = "form_{$field['id']}";
                    $required     = !empty($field['required']);
                @endphp
                <div class="mb-3">
                    <label class="form-label text-xs">{{ $label }}@if($required)<span class="text-red-500 ml-0.5">*</span>@endif</label>
                    @if($field['type'] === 'textarea')
                        <textarea name="{{ $inputName }}" rows="2" class="form-input text-sm" @if($required) required @endif>{{ $currentValue }}</textarea>
                    @elseif($field['type'] === 'select')
                        <select name="{{ $inputName }}" class="form-select text-sm" @if($required) required @endif>
                            @foreach($field['options'] ?? [] as $opt)
                            <option value="{{ $opt['value'] }}" {{ $currentValue == $opt['value'] ? 'selected' : '' }}>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    @elseif($field['type'] === 'number')
                        <input type="number" name="{{ $inputName }}" value="{{ $currentValue }}" class="form-input text-sm" @if($required) required @endif>
                    @else
                        <input type="{{ $field['type'] === 'date' ? 'date' : 'text' }}" name="{{ $inputName }}" value="{{ $currentValue }}" class="form-input text-sm" @if($required) required @endif>
                    @endif
                </div>
                @endforeach
                <button type="submit" class="btn-warning text-sm w-full flex items-center justify-center space-x-1 mt-1">
                    <i class="ti ti-send"></i>
                    <span>{{ app()->getLocale() === 'th' ? 'ส่งคำร้องใหม่' : 'Resubmit' }}</span>
                </button>
            </form>
        </div>
        @endif

        <!-- Current assignee -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="font-semibold mb-3 text-sm">{{ app()->getLocale() === 'th' ? 'ข้อมูลงาน' : 'Info' }}</h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ app()->getLocale() === 'th' ? 'สถานะ' : 'Status' }}</dt>
                    <dd><span class="status-badge status-{{ $submission->status }}">{{ ucfirst(str_replace('_', ' ', $submission->status)) }}</span></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ app()->getLocale() === 'th' ? 'ขั้นตอน' : 'Step' }}</dt>
                    <dd class="font-medium">
                        @if($isGraphFlow)
                            {{ $currentNodeForDisplay['label'] ?? ($submission->current_step ?? '-') }}
                        @else
                            {{ $submission->current_step }} / {{ $submission->app->approvalSteps->count() }}
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ app()->getLocale() === 'th' ? 'ผู้รับผิดชอบ' : 'Assignee' }}</dt>
                    <dd class="font-medium">{{ $submission->latestAssignment?->assignee?->name ?? '-' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Due</dt>
                    <dd class="font-medium {{ $submission->isOverdue() ? 'text-red-500' : '' }}">
                        {{ $submission->latestAssignment?->due_date?->format('d/m/Y') ?? '-' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">{{ app()->getLocale() === 'th' ? 'ความคืบหน้า' : 'Progress' }}</dt>
                    <dd class="font-medium">{{ $submission->progress }}%</dd>
                </div>
            </dl>
            <div class="mt-3">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-indigo-500 h-2 rounded-full transition-all" style="width:{{ $submission->progress }}%"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
