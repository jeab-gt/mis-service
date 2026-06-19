<?php

namespace App\Http\Controllers;

use App\Models\ChecksheetRecord;
use App\Models\ChecksheetRecordValue;
use App\Models\ChecksheetTemplate;
use App\Models\Master;
use Illuminate\Http\Request;

class ChecksheetEntryController extends Controller
{
    public function index()
    {
        $templates = ChecksheetTemplate::where('is_active', true)
            ->withCount('parameters')
            ->orderBy('name')
            ->get();

        return view('checksheets.entry-index', compact('templates'));
    }

    public function records(ChecksheetTemplate $template)
    {
        $records = $template->records()
            ->with(['factory', 'timeSlot', 'submitter'])
            ->withCount(['values as alert_count' => function ($q) {
                $q->where('is_alert', true);
            }])
            ->latest('record_date')
            ->paginate(20);

        return view('checksheets.records', compact('template', 'records'));
    }

    public function fill(ChecksheetTemplate $template)
    {
        $parameters = $template->parameters()->where('is_active', true)->orderBy('sort_order')->get();
        $timeSlots  = $template->timeSlots()->orderBy('sort_order')->get();
        $factories  = Master::where('type', 'factory')->where('is_active', true)->orderBy('name_th')->get();

        return view('checksheets.fill', compact('template', 'parameters', 'timeSlots', 'factories'));
    }

    public function store(Request $request, ChecksheetTemplate $template)
    {
        $request->validate([
            'record_date' => 'required|date',
            'factory_id'  => 'required|exists:masters,id',
            'action'      => 'nullable|in:draft,submit',
            'values'      => 'nullable|array',
        ]);

        $status = $request->input('action') === 'submit' ? 'submitted' : 'draft';

        // Form sends values[paramId_slotId] = value (or values[paramId] if no slots)
        // Group by slotId → create one ChecksheetRecord per time slot
        $bySlot = [];
        foreach ($request->input('values', []) as $key => $value) {
            if (str_contains((string) $key, '_')) {
                [$paramId, $slotId] = explode('_', $key, 2);
            } else {
                $paramId = $key;
                $slotId  = 'none';
            }
            $bySlot[$slotId][(int) $paramId] = $value;
        }

        // If form has no slot columns, fall back to a single record
        if (empty($bySlot)) {
            $bySlot = ['none' => []];
        }

        $parameters = $template->parameters()->where('is_active', true)->get()->keyBy('id');
        $created    = 0;

        foreach ($bySlot as $slotId => $paramValues) {
            // Skip slots where every value is blank
            $hasData = collect($paramValues)->filter(fn($v) => $v !== null && $v !== '')->isNotEmpty();
            if (!$hasData) continue;

            $record = ChecksheetRecord::create([
                'template_id'  => $template->id,
                'factory_id'   => $request->factory_id,
                'record_date'  => $request->record_date,
                'time_slot_id' => $slotId !== 'none' ? (int) $slotId : null,
                'status'       => $status,
                'submitted_by' => $status === 'submitted' ? auth()->id() : null,
                'submitted_at' => $status === 'submitted' ? now() : null,
            ]);

            foreach ($paramValues as $paramId => $value) {
                $param      = $parameters->get($paramId);
                $alertLevel = $param ? $param->checkValue($value) : null;

                ChecksheetRecordValue::create([
                    'record_id'    => $record->id,
                    'parameter_id' => $paramId,
                    'value'        => $value !== null && $value !== '' ? (string) $value : null,
                    'is_alert'     => $alertLevel !== null,
                    'alert_level'  => $alertLevel,
                    'recorded_by'  => auth()->id(),
                ]);
            }
            $created++;
        }

        $msg = $status === 'submitted'
            ? "บันทึกและส่งสำเร็จ ({$created} เวลา)"
            : "บันทึก Draft สำเร็จ ({$created} เวลา)";

        return redirect()->route('checksheets.records', $template)->with('success', $msg);
    }

    public function submit(ChecksheetRecord $record)
    {
        $record->update([
            'status'       => 'submitted',
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
        ]);

        return redirect()->back()->with('success', 'ส่งข้อมูลสำเร็จ');
    }
}
