<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ChecksheetArchiveService;
use Illuminate\Http\Request;

class DataManagementController extends Controller
{
    public function __construct(private ChecksheetArchiveService $archiveService)
    {
    }

    public function index()
    {
        $hotStats      = $this->archiveService->getHotTableStats();
        $archiveTables = $this->archiveService->getArchiveTables();

        return view('admin.data-management', compact('hotStats', 'archiveTables'));
    }

    public function archive(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020|max:' . (now()->year - 1),
        ]);

        try {
            $rowCount = $this->archiveService->archiveYear($data['year'], auth()->user());
            return redirect()->route('admin.data-management.index')
                ->with('success', "Archive ปี {$data['year']} สำเร็จ จำนวน {$rowCount} rows");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }

    public function dropArchive(Request $request)
    {
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403, 'Only super_admin can drop archive tables');
        }

        $data = $request->validate([
            'table_name' => 'required|string',
        ]);

        try {
            $this->archiveService->dropArchive($data['table_name'], auth()->user());
            return redirect()->route('admin.data-management.index')
                ->with('success', "ลบตาราง {$data['table_name']} สำเร็จ");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
}
