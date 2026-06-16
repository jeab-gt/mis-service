@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Kanit', sans-serif; background-color: #f4f7f6; }
    .inventory-container { margin-top: 30px; padding: 20px; }
    .header-section { text-align: center; margin-bottom: 30px; }
    .green-badge {
        background-color: #008751; color: white; padding: 12px 30px;
        border-radius: 50px; display: inline-block; font-size: 20px;
        font-weight: 500; box-shadow: 0 4px 15px rgba(0, 135, 81, 0.2);
    }
    .data-card {
        background: white; border-radius: 15px; padding: 25px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05); border-top: 6px solid #008751;
    }
    .table thead th {
        background-color: #f8f9fa; color: #7f8c8d; font-weight: 500;
        text-transform: uppercase; font-size: 13px; border-bottom: 2px solid #eee;
    }
    .material-code {
        font-family: 'Courier New', Courier, monospace; font-weight: bold;
        color: #2980b9; background: #ebf5fb; padding: 4px 8px; border-radius: 4px;
    }
    .stock-qty { font-size: 16px; font-weight: 500; color: #2c3e50; }
    .loading-spinner { display: none; text-align: center; padding: 40px; }
</style>

<div class="container inventory-container">
    <div class="header-section">
        <div class="green-badge">NET : SAP INVENTORY TOP 100</div>
        <p class="text-muted" style="margin-top: 15px;">
            แสดงรายการสินค้าที่มีสต็อกคงเหลือ 100 อันดับแรก (โรงงาน: {{ session('UserPlant', '2400') }})
        </p>
    </div>

    <div class="data-card">
        <div class="table-responsive">
            <table class="table table-hover" id="inventoryTable">
                <thead>
                    <tr>
                        <th width="10%">#</th>
                        <th width="30%">Material Number</th>
                        <th width="15%">Plant</th>
                        <th width="15%">Storage Loc</th>
                        <th width="30%" class="text-right">Unrestricted Stock</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div id="loader" class="loading-spinner">
            <img src="https://i.gifer.com/ZZ5H.gif" width="50" alt="loading">
            <p style="margin-top: 10px; color: #95a5a6;">กำลังเชื่อมต่อ SAP Client 376...</p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        loadInventoryData();
    });

    function loadInventoryData() {
        $("#loader").show();
        $("#inventoryTable tbody").empty();

        $.ajax({
            url: "{{ route('api.get-inventory-top100') }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function (res) {
                $("#loader").hide();
                if (res.success) {
                    let rows = "";
                    $.each(res.data, function (index, item) {
                        rows += `<tr>
                            <td>${index + 1}</td>
                            <td><span class="material-code">${item.Material}</span></td>
                            <td><span class="label label-default">${item.Plant}</span></td>
                            <td><b>${item.SLoc}</b></td>
                            <td class="text-right stock-qty text-primary">${Number(item.Stock).toLocaleString()}</td>
                        </tr>`;
                    });
                    $("#inventoryTable tbody").html(rows);
                } else {
                    alert("Error: " + res.message);
                }
            },
            error: function () {
                $("#loader").hide();
                alert("ไม่สามารถเชื่อมต่อ Server ได้");
            }
        });
    }
</script>
@endsection
