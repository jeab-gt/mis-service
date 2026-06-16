<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Master;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    private const TYPES = ['company', 'factory', 'plant', 'department', 'section', 'team', 'line'];

    public function index()
    {
        $roots = Master::whereNull('parent_id')
            ->withCount('users')
            ->with(['children' => fn($q) => $q->withCount('users')
                ->with(['children' => fn($q2) => $q2->withCount('users')
                    ->with(['children' => fn($q3) => $q3->withCount('users')
                        ->with(['children' => fn($q4) => $q4->withCount('users')
                        ])
                    ])
                ])
            ])
            ->orderBy('sort_order')
            ->get();

        return view('master.index', compact('roots'));
    }

    public function create(Request $request)
    {
        $parents          = Master::active()->orderBy('name_th')->get();
        $selectedParentId = $request->get('parent_id');
        $suggestedType    = $request->get('suggested_type');
        return view('master.form', compact('parents', 'selectedParentId', 'suggestedType'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id'  => 'nullable|exists:masters,id',
            'type'       => 'required|in:' . implode(',', self::TYPES),
            'code'       => 'required|string|max:50|unique:masters,code',
            'name_th'    => 'required|string|max:255',
            'name_en'    => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $master = Master::create($data);

        // Auto-compute factory_id for child nodes
        if ($master->parent_id && in_array($master->type, ['plant', 'department', 'section', 'team', 'line'])) {
            $master->update(['factory_id' => $master->parent->resolveFactoryId()]);
        }

        return redirect()->route('admin.masters.index')->with('success', 'สร้างข้อมูลสำเร็จ');
    }

    public function edit(Master $master)
    {
        $parents = Master::active()->where('id', '!=', $master->id)->orderBy('sort_order')->get();
        $parent  = $master->parent;
        return view('master.form', compact('master', 'parents', 'parent'));
    }

    public function update(Request $request, Master $master)
    {
        $data = $request->validate([
            'parent_id'  => 'nullable|exists:masters,id',
            'type'       => 'required|in:' . implode(',', self::TYPES),
            'code'       => 'required|string|max:50|unique:masters,code,' . $master->id,
            'name_th'    => 'required|string|max:255',
            'name_en'    => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'boolean',
        ]);

        if ($data['parent_id'] && $this->wouldCreateCycle($master, (int) $data['parent_id'])) {
            return back()->withErrors(['parent_id' => 'ไม่สามารถเลือก parent ที่เป็น child ของตัวเองได้']);
        }

        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        // Recompute factory_id if parent changed
        if (isset($data['parent_id']) && $data['parent_id'] != $master->parent_id) {
            if ($data['parent_id'] && in_array($data['type'], ['plant', 'department', 'section', 'team', 'line'])) {
                $parent              = Master::find($data['parent_id']);
                $data['factory_id']  = $parent?->resolveFactoryId();
            } else {
                $data['factory_id'] = null;
            }
        }

        $master->update($data);

        return redirect()->route('admin.masters.index')->with('success', 'แก้ไขข้อมูลสำเร็จ');
    }

    public function destroy(Master $master)
    {
        if ($master->users()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้ เนื่องจากมีผู้ใช้งานในหน่วยนี้');
        }
        if ($master->children()->exists()) {
            return back()->with('error', 'ไม่สามารถลบได้ เนื่องจากมี child nodes อยู่');
        }
        $master->delete();
        return back()->with('success', 'ลบข้อมูลสำเร็จ');
    }

    protected function wouldCreateCycle(Master $master, int $parentId): bool
    {
        $current = Master::find($parentId);
        while ($current) {
            if ($current->id === $master->id) return true;
            $current = $current->parent;
        }
        return false;
    }
}
