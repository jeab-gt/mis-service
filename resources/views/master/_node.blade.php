<div x-data="{ open: true }">
    <div class="flex items-center py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-750 group"
         style="padding-left: {{ $level * 1.5 + 0.75 }}rem">
        @if($node->children->count())
        <button @click="open = !open" class="mr-2 text-gray-400 hover:text-gray-600">
            <i class="ti text-sm" :class="open ? 'ti-chevron-down' : 'ti-chevron-right'"></i>
        </button>
        @else
        <span class="mr-2 w-4 inline-block"></span>
        @endif

        <i class="ti {{ \App\Models\Master::typeIcon($node->type) }} mr-2 text-indigo-500"></i>

        <div class="flex-1 min-w-0">
            <span class="font-medium text-sm">{{ $node->name_th }}</span>
            <span class="text-xs text-gray-400 ml-2">{{ $node->name_en }}</span>
            <span class="text-xs text-gray-400 ml-2">[{{ $node->code }}]</span>
        </div>

        <span class="text-xs px-2 py-0.5 rounded-full mr-2 {{ \App\Models\Master::typeBadgeColor($node->type) }}">
            {{ \App\Models\Master::typeLabel($node->type) }}
        </span>

        @php $userCount = $node->users_count ?? 0; @endphp
        @if($userCount > 0)
        <span class="text-xs text-gray-400 mr-3 flex items-center">
            <i class="ti ti-user text-xs mr-0.5"></i>{{ $userCount }}
        </span>
        @else
        <span class="mr-3 w-8 inline-block"></span>
        @endif

        @if(!$node->is_active)
        <span class="text-xs bg-red-100 text-red-500 px-2 py-0.5 rounded-full mr-2">{{ app()->getLocale() === 'th' ? 'ปิดใช้งาน' : 'Inactive' }}</span>
        @endif

        <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
            @can('master.create')
            <a href="{{ route('admin.masters.create', ['parent_id' => $node->id, 'suggested_type' => $node->childType()]) }}"
               class="p-1 text-green-500 hover:text-green-700" title="{{ app()->getLocale() === 'th' ? 'เพิ่ม Child' : 'Add Child' }}">
                <i class="ti ti-plus text-sm"></i>
            </a>
            @endcan
            @can('master.edit')
            <a href="{{ route('admin.masters.edit', $node) }}"
               class="p-1 text-blue-500 hover:text-blue-700" title="{{ __('common.edit') }}">
                <i class="ti ti-edit text-sm"></i>
            </a>
            @endcan
            @can('master.delete')
            <form method="POST" action="{{ route('admin.masters.destroy', $node) }}"
                  onsubmit="return confirm('{{ __('common.confirm_delete') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="p-1 text-red-400 hover:text-red-600" title="{{ __('common.delete') }}">
                    <i class="ti ti-trash text-sm"></i>
                </button>
            </form>
            @endcan
        </div>
    </div>

    @if($node->children->count())
    <div x-show="open">
        @foreach($node->children as $child)
            @include('master._node', ['node' => $child, 'level' => $level + 1])
        @endforeach
    </div>
    @endif
</div>
