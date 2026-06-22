<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppCategory;
use Illuminate\Http\Request;

class AppCategoryController extends Controller
{
    public function index()
    {
        $categories = AppCategory::withCount(['apps', 'checksheets'])
            ->orderBy('sort_order')
            ->orderBy('name_th')
            ->get();

        return view('admin.app-categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_th'    => 'required|string|max:100',
            'name_en'    => 'nullable|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $data['created_by'] = auth()->id();
        $data['icon']       = $data['icon'] ?? 'ti-category';
        $data['color']      = $data['color'] ?? 'indigo';
        $data['sort_order'] = $data['sort_order'] ?? 0;

        AppCategory::create($data);

        return back()->with('success', 'สร้างหมวดหมู่สำเร็จ');
    }

    public function update(Request $request, AppCategory $appCategory)
    {
        $data = $request->validate([
            'name_th'    => 'required|string|max:100',
            'name_en'    => 'nullable|string|max:100',
            'icon'       => 'nullable|string|max:50',
            'color'      => 'nullable|string|max:30',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $appCategory->update($data);

        return back()->with('success', 'แก้ไขหมวดหมู่สำเร็จ');
    }

    public function destroy(AppCategory $appCategory)
    {
        $appCategory->delete();
        return back()->with('success', 'ลบหมวดหมู่สำเร็จ');
    }
}
