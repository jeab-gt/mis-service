<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SAPNWRFC\Connection;
use SAPNWRFC\Exception as SapException;
use Illuminate\Support\Facades\Log;

class SapInventoryController extends Controller
{
    protected $config;

    public function __construct()
    {
        // ดึงค่าการเชื่อมต่อจาก config/sap.php หรือ .env โดยตรง
        $this->config = [
            'ashost' => env('SAP_ASHOST'), // 160.21.242.153
            'sysnr'  => env('SAP_SYSNR'),  // 01
            'client' => env('SAP_CLIENT'), // 376
            'user'   => env('SAP_USER'),   // A100R97183
            'passwd' => env('SAP_PASSWD'), // initpass
            'lang'   => env('SAP_LANG', 'EN'),
        ];
    }

    /**
     * แสดงหน้าหลัก (View100 ที่คุณวางทับใน index.blade.php)
     */
    public function index()
    {
        // ดึงค่า Plant จาก Session หรือ Default เป็น 2400
        $selectedPlant = session('UserPlant', '2400');
        return view('inventory.index', compact('selectedPlant'));
    }

    /**
     * ดึงข้อมูลสต็อก 100 อันดับแรกจากตาราง MARD
     * (สำหรับตารางในหน้า View100)
     */
    public function getInventoryTop100()
    {
        try {
            $conn = new Connection($this->config);
            $f = $conn->lookup('RFC_READ_TABLE');

            $plant = session('UserPlant', '2400');

            $result = $f->invoke([
                'QUERY_TABLE' => 'MARD',
                'DELIMITER'   => '|',
                'ROWCOUNT'    => 100,
                'OPTIONS'     => [
                    ['TEXT' => "WERKS = '$plant' AND LABST > 0"]
                ],
                'FIELDS'      => [
                    ['FIELDNAME' => 'MATNR'],
                    ['FIELDNAME' => 'WERKS'],
                    ['FIELDNAME' => 'LGORT'],
                    ['FIELDNAME' => 'LABST']
                ],
            ]);

            $inventoryList = [];
            foreach ($result['DATA'] as $row) {
                $values = explode('|', $row['WA']);
                $inventoryList[] = [
                    'Material' => ltrim($values[0], '0'), // ตัดเลข 0 ข้างหน้า
                    'Plant'    => $values[1],
                    'SLoc'     => $values[2],
                    'Stock'    => (float)$values[3]
                ];
            }

            return response()->json([
                'success' => true,
                'data'    => $inventoryList
            ]);

        } catch (SapException $e) {
            Log::error("SAP Top 100 Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "SAP Error: " . $e->getMessage()
            ]);
        }
    }

    /**
     * ดึงจำนวนสินค้าคงเหลือรายชิ้น (BAPI_MATERIAL_AVAILABILITY)
     */
    public function getStock(Request $request)
    {
        try {
            $material = $request->input('material');
            if (empty($material)) {
                return response()->json(['success' => false, 'message' => 'กรุณาระบุรหัสสินค้า']);
            }

            $conn = new Connection($this->config);
            $f = $conn->lookup('BAPI_MATERIAL_AVAILABILITY');

            // เติม 0 ให้ครบ 18 หลักเหมือน .PadLeft(18, '0')
            $matCode = str_pad(trim($material), 18, '0', STR_PAD_LEFT);
            $plant = session('UserPlant', '2400');

            $result = $f->invoke([
                'MATERIAL' => $matCode,
                'PLANT'    => $plant,
                'UNIT'     => 'EA',
            ]);

            return response()->json([
                'success'  => true,
                'quantity' => (float)$result['AV_QTY_PLANNED']
            ]);

        } catch (SapException $e) {
            return response()->json([
                'success' => false,
                'message' => "SAP Error: " . $e->getMessage()
            ]);
        }
    }
}
