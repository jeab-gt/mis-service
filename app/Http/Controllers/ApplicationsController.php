<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppCategory;
use App\Models\ChecksheetTemplate;
use Illuminate\Http\Request;

class ApplicationsController extends Controller
{
    public function index()
    {
        $user      = auth()->user();
        $userRoles = $user->getRoleNames()->toArray();
        $factoryId = $user->factory_id;

        $apps = App::where('is_active', true)
            ->where(function ($q) use ($userRoles) {
                $q->whereNull('allowed_roles');
                foreach ($userRoles as $role) {
                    $q->orWhereJsonContains('allowed_roles', $role);
                }
            })
            ->where(function ($q) use ($factoryId) {
                $q->whereNull('allowed_factories');
                if ($factoryId) {
                    $q->orWhereJsonContains('allowed_factories', (int) $factoryId);
                }
            })
            ->with(['appCategory', 'primaryDashboard'])
            ->orderBy('name')
            ->get();

        $checksheets = ChecksheetTemplate::where('is_active', true)
            ->where(function ($q) use ($userRoles) {
                $q->whereNull('allowed_roles');
                foreach ($userRoles as $role) {
                    $q->orWhereJsonContains('allowed_roles', $role);
                }
            })
            ->where(function ($q) use ($factoryId) {
                $q->whereNull('allowed_factories');
                if ($factoryId) {
                    $q->orWhereJsonContains('allowed_factories', (int) $factoryId);
                }
            })
            ->with(['category', 'primaryDashboard'])
            ->orderBy('name')
            ->get();

        $categories = AppCategory::orderBy('sort_order')->orderBy('name_th')->get();

        // Build grouped structure: category_id => [apps => [...], checksheets => [...]]
        $grouped = [];

        foreach ($categories as $cat) {
            $grouped[$cat->id] = [
                'category'    => $cat,
                'apps'        => $apps->where('category_id', $cat->id)->values(),
                'checksheets' => $checksheets->where('category_id', $cat->id)->values(),
            ];
        }

        // Uncategorized
        $uncatApps        = $apps->whereNull('category_id')->values();
        $uncatChecksheets = $checksheets->whereNull('category_id')->values();

        return view('applications.index', compact('grouped', 'uncatApps', 'uncatChecksheets'));
    }
}
