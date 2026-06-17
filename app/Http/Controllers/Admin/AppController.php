<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\Flow;
use App\Models\FormTemplate;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index()
    {
        $apps = App::withCount('submissions')
            ->with(['initialFormTemplate', 'flow'])
            ->latest()
            ->paginate(12);

        return view('apps.index', compact('apps'));
    }

    public function create()
    {
        $formTemplates = FormTemplate::where('is_active', true)->orderBy('name')->get();
        $flows         = Flow::where('is_active', true)->orderBy('name')->get();
        return view('apps.edit', ['formTemplates' => $formTemplates, 'flows' => $flows, 'app' => null]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'slug'                      => 'required|string|max:100|unique:apps,slug|alpha_dash',
            'category'                  => 'required|string|max:50',
            'description'               => 'nullable|string',
            'icon'                      => 'nullable|string|max:50',
            'is_active'                 => 'boolean',
            'initial_form_template_id'  => 'nullable|exists:form_templates,id',
            'revision_form_template_id' => 'nullable|exists:form_templates,id',
            'flow_id'                   => 'nullable|exists:flows,id',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth()->id();

        App::create($data);

        return redirect()->route('admin.apps.index')->with('success', 'สร้าง App สำเร็จ');
    }

    public function edit(App $app)
    {
        $formTemplates = FormTemplate::where('is_active', true)->orderBy('name')->get();
        $flows         = Flow::where('is_active', true)->orderBy('name')->get();
        return view('apps.edit', compact('app', 'formTemplates', 'flows'));
    }

    public function update(Request $request, App $app)
    {
        $data = $request->validate([
            'name'                      => 'required|string|max:255',
            'slug'                      => 'required|string|max:100|unique:apps,slug,' . $app->id . '|alpha_dash',
            'category'                  => 'required|string|max:50',
            'description'               => 'nullable|string',
            'icon'                      => 'nullable|string|max:50',
            'is_active'                 => 'boolean',
            'initial_form_template_id'  => 'nullable|exists:form_templates,id',
            'revision_form_template_id' => 'nullable|exists:form_templates,id',
            'flow_id'                   => 'nullable|exists:flows,id',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $app->update($data);

        return redirect()->route('admin.apps.index')->with('success', 'แก้ไข App สำเร็จ');
    }

    public function destroy(App $app)
    {
        $app->delete();
        return back()->with('success', 'ลบ App สำเร็จ');
    }

    public function preview(App $app)
    {
        $app->load('initialFormTemplate');
        return view('apps.preview', compact('app'));
    }
}
