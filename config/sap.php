<?php
// config/sap.php

return [
    'default' => [
        'ashost' => env('SAP_ASHOST'), // 160.21.242.153
        'sysnr'  => env('SAP_SYSNR'),  // 01
        'client' => env('SAP_CLIENT'), // 376
        'user'   => env('SAP_USER'),   // A10RA02104
        'passwd' => env('SAP_PASSWD'), // initpass01
        'lang'   => env('SAP_LANG', 'EN'),
    ],
];
