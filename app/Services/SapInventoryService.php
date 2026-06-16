<?php
namespace App\Services;

use SAPNWRFC\Connection;
use SAPNWRFC\Exception as SapException;

class SapInventoryService
{
    protected $config;

    public function __construct()
    {
        // ดึงค่าจาก .env ที่คุณ copy ไปวาง [cite: 1]
        $this->config = [
            'ashost' => env('SAP_ASHOST'), // 160.21.242.153
            'sysnr'  => env('SAP_SYSNR'),  // 01
            'client' => env('SAP_CLIENT'), // 376
            'user'   => env('SAP_USER'),   // A10RA02104
            'passwd' => env('SAP_PASSWD'), // initpass01
            'lang'   => env('SAP_LANG', 'EN'),
        ];
    }

    public function getMaterialAvailability($material, $plant = '2400')
    {
        try {
            $conn = new Connection($this->config);
            // ใช้ BAPI ตัวเดียวกับในไฟล์ C#
            $f = $conn->lookup('BAPI_MATERIAL_AVAILABILITY');

            // เติม 0 ข้างหน้าให้ครบ 18 หลักเหมือน matCode.PadLeft(18, '0')
            $matCode = str_pad($material, 18, '0', STR_PAD_LEFT);

            $result = $f->invoke([
                'MATERIAL' => $matCode,
                'PLANT'    => $plant,
                'UNIT'     => 'EA',
            ]);

            return [
                'success' => true,
                'quantity' => $result['AV_QTY_PLANNED'] // ยอดรวมที่ใช้งานได้
            ];
        } catch (SapException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
