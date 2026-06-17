<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FormTemplate;
use App\Models\OptionSet;
use Illuminate\Http\Request;

class FormTemplateController extends Controller
{
    public function index()
    {
        $templates = FormTemplate::withCount([
            'appsAsInitial',
            'appsAsRevision',
            'flowNodes',
        ])->latest()->get();

        return view('form_templates.index', compact('templates'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:50',
        ]);

        $template = FormTemplate::create([
            ...$data,
            'schema'     => ['fields' => []],
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.form-templates.designer', $template)
            ->with('success', 'สร้าง Form Template สำเร็จ');
    }

    public function designer(FormTemplate $formTemplate)
    {
        $optionSets = OptionSet::select('id', 'code', 'name_th', 'name_en', 'source_type')->get();
        return view('form_templates.designer', compact('formTemplate', 'optionSets'));
    }

    public function save(Request $request, FormTemplate $formTemplate)
    {
        $request->validate(['schema' => 'required|array']);
        $formTemplate->update(['schema' => $request->schema]);
        return response()->json(['message' => 'Saved']);
    }

    public function update(Request $request, FormTemplate $formTemplate)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|string|max:50',
            'is_active'   => 'boolean',
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $formTemplate->update($data);
        return back()->with('success', 'อัปเดตสำเร็จ');
    }

    public function duplicate(FormTemplate $formTemplate)
    {
        $copy = $formTemplate->replicate();
        $copy->name       = $formTemplate->name . ' (Copy)';
        $copy->created_by = auth()->id();
        $copy->save();
        return redirect()->route('admin.form-templates.designer', $copy)
            ->with('success', 'ทำสำเนาสำเร็จ');
    }

    public function destroy(FormTemplate $formTemplate)
    {
        $usedCount = $formTemplate->appsAsInitial()->count()
            + $formTemplate->appsAsRevision()->count()
            + $formTemplate->flowNodes()->count();

        if ($usedCount > 0) {
            return back()->withErrors(['error' => 'ไม่สามารถลบได้ เนื่องจากมี App หรือ Flow Node ใช้งาน Template นี้อยู่']);
        }

        $formTemplate->delete();
        return back()->with('success', 'ลบ Form Template สำเร็จ');
    }
}
