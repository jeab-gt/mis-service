<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OptionSet;
use Illuminate\Http\Request;

class OptionSetController extends Controller
{
    public function index()
    {
        $optionSets = OptionSet::withCount('items')->latest()->paginate(15);
        return view('option_sets.index', compact('optionSets'));
    }

    public function create()
    {
        return view('option_sets.form');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'              => 'required|string|max:50|unique:option_sets,code|alpha_dash',
            'name_th'           => 'required|string|max:255',
            'name_en'           => 'required|string|max:255',
            'source_type'       => 'required|in:static,master,users,roles',
            'master_type'       => 'nullable|string|max:50',
            'filter_by_factory' => 'boolean',
            'description'       => 'nullable|string',
            'items'             => 'nullable|array',
            'items.*.value'     => 'required_with:items|string|max:100',
            'items.*.label_th'  => 'required_with:items|string|max:255',
            'items.*.label_en'  => 'required_with:items|string|max:255',
        ]);

        $optionSet = OptionSet::create([
            'code'              => $data['code'],
            'name_th'           => $data['name_th'],
            'name_en'           => $data['name_en'],
            'source_type'       => $data['source_type'],
            'master_type'       => $data['master_type'] ?? null,
            'filter_by_factory' => $request->boolean('filter_by_factory'),
            'description'       => $data['description'] ?? null,
        ]);

        if ($data['source_type'] === 'static' && !empty($data['items'])) {
            foreach ($data['items'] as $i => $item) {
                $optionSet->items()->create([
                    'value'      => $item['value'],
                    'label_th'   => $item['label_th'],
                    'label_en'   => $item['label_en'],
                    'sort_order' => $i,
                    'is_active'  => true,
                ]);
            }
        }

        return redirect()->route('admin.option-sets.index')->with('success', 'สร้าง Option Set สำเร็จ');
    }

    public function edit(OptionSet $optionSet)
    {
        $optionSet->load('items');
        return view('option_sets.form', compact('optionSet'));
    }

    public function update(Request $request, OptionSet $optionSet)
    {
        $data = $request->validate([
            'name_th'           => 'required|string|max:255',
            'name_en'           => 'required|string|max:255',
            'source_type'       => 'required|in:static,master,users,roles',
            'master_type'       => 'nullable|string|max:50',
            'filter_by_factory' => 'boolean',
            'description'       => 'nullable|string',
            'items'             => 'nullable|array',
            'items.*.value'     => 'required_with:items|string|max:100',
            'items.*.label_th'  => 'required_with:items|string|max:255',
            'items.*.label_en'  => 'required_with:items|string|max:255',
        ]);

        $optionSet->update([
            'name_th'           => $data['name_th'],
            'name_en'           => $data['name_en'],
            'source_type'       => $data['source_type'],
            'master_type'       => $data['master_type'] ?? null,
            'filter_by_factory' => $request->boolean('filter_by_factory'),
            'description'       => $data['description'] ?? null,
        ]);

        if ($data['source_type'] === 'static') {
            $optionSet->items()->delete();
            foreach ($data['items'] ?? [] as $i => $item) {
                $optionSet->items()->create([
                    'value'      => $item['value'],
                    'label_th'   => $item['label_th'],
                    'label_en'   => $item['label_en'],
                    'sort_order' => $i,
                    'is_active'  => true,
                ]);
            }
        }

        return redirect()->route('admin.option-sets.index')->with('success', 'แก้ไข Option Set สำเร็จ');
    }

    public function destroy(OptionSet $optionSet)
    {
        $optionSet->delete();
        return back()->with('success', 'ลบ Option Set สำเร็จ');
    }

    public function options(OptionSet $optionSet)
    {
        $factoryId = auth()->user()?->factory_id;
        return response()->json($optionSet->getOptions($factoryId));
    }
}
