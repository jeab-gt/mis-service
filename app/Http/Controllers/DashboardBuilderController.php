<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\ChecksheetTemplate;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardBuilderController extends Controller
{
    public function index()
    {
        $dashboards = Dashboard::withCount('widgets')
            ->with('creator')
            ->latest()
            ->paginate(12);

        return view('dashboards.index', compact('dashboards'));
    }

    public function create()
    {
        $apps = App::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']);

        return view('dashboards.create', compact('apps'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => 'required|string|max:255',
            'factory_scope'  => 'required|in:own_factory,specific,all',
            'is_public'      => 'boolean',
            'primary_app_id' => 'nullable|exists:apps,id',
        ]);

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i    = 1;
        while (Dashboard::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $dashboard = Dashboard::create([
            'name'           => $data['name'],
            'slug'           => $slug,
            'factory_scope'  => $data['factory_scope'],
            'is_public'      => $request->boolean('is_public', false),
            'primary_app_id' => $data['primary_app_id'] ?? null,
            'created_by'     => auth()->id(),
        ]);

        return redirect()->route('dashboards.edit', $dashboard)
            ->with('success', 'สร้าง Dashboard สำเร็จ');
    }

    public function show(Dashboard $dashboard)
    {
        $dashboard->load('widgets');

        return view('dashboards.show', compact('dashboard'));
    }

    public function edit(Dashboard $dashboard)
    {
        $dashboard->load('widgets');
        $templates = ChecksheetTemplate::where('is_active', true)
            ->with('parameters')
            ->orderBy('name')
            ->get();

        return view('dashboards.edit', compact('dashboard', 'templates'));
    }

    public function saveLayout(Request $request, Dashboard $dashboard)
    {
        $data = $request->validate([
            'widgets'               => 'nullable|array',
            'widgets.*.id'          => 'nullable|integer',
            'widgets.*.widget_type' => 'required|in:line_chart,bar_chart,gauge,heatmap,kpi_card,data_table',
            'widgets.*.title'       => 'required|string|max:255',
            'widgets.*.title_en'    => 'nullable|string|max:255',
            'widgets.*.config'      => 'nullable|array',
            'widgets.*.pos_x'       => 'integer|min:0',
            'widgets.*.pos_y'       => 'integer|min:0',
            'widgets.*.width'       => 'integer|min:1',
            'widgets.*.height'      => 'integer|min:1',
        ]);

        $dashboard->widgets()->delete();

        foreach ($data['widgets'] ?? [] as $wData) {
            $dashboard->widgets()->create([
                'widget_type' => $wData['widget_type'],
                'title'       => $wData['title'],
                'title_en'    => $wData['title_en'] ?? null,
                'config'      => $wData['config'] ?? null,
                'pos_x'       => $wData['pos_x'] ?? 0,
                'pos_y'       => $wData['pos_y'] ?? 0,
                'width'       => $wData['width'] ?? 6,
                'height'      => $wData['height'] ?? 4,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'บันทึก Layout สำเร็จ',
        ]);
    }

    public function destroy(Dashboard $dashboard)
    {
        $dashboard->delete();

        return redirect()->route('dashboards.index')
            ->with('success', 'ลบ Dashboard สำเร็จ');
    }
}
