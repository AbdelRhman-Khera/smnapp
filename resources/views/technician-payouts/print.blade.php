@php
    $technician = $payout->technician;
    $technicianName = trim(($technician?->first_name ?? '') . ' ' . ($technician?->last_name ?? '')) ?: 'Technician #' . $payout->technician_id;

    $earnings = $payout->earnings;

    // The earning is created the moment the request is completed,
    // so its created_at is the request completion date.
    $requestDates = $earnings
        ->map(fn ($earning) => $earning->created_at)
        ->filter()
        ->sort()
        ->values();

    $fromDate = $requestDates->first();
    $toDate = $requestDates->last();

    $typeLabels = [
        'new_installation' => 'تركيب جديد',
        'regular_maintenance' => 'صيانة دورية',
        'emergency_maintenance' => 'صيانة طارئة',
        'warranty' => 'ضمان',
    ];

    $approverName = $payout->processedBy?->name ?: '-';
    $amountFormatted = number_format((float) $payout->total_amount, 2);
    $logo = asset('assets/logo.png');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إذن صرف مستحقات #{{ $payout->id }} - Samnan Water Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    <style>
        :root{--blue:#20419C;--blue-dark:#19347D;--ink:#2B2F3A;--muted:#8A8F99;--row:#F4F5F7;--line:#E6E8EE;--page:#ECEEF1;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Tajawal',sans-serif;background:var(--page);color:var(--ink);display:flex;justify-content:center;padding:40px 16px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
        .voucher{background:#fff;width:794px;max-width:100%;padding:48px 52px 36px;box-shadow:0 18px 50px rgba(20,30,70,.10);position:relative;}
        .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid var(--blue);padding-bottom:22px;margin-bottom:26px;}
        .head .brand img{height:58px;display:block;}
        .head .doc-meta{text-align:left;font-size:12.5px;color:var(--muted);line-height:1.9;}
        .head .doc-meta b{color:var(--ink);font-weight:700;}
        .title{text-align:center;margin-bottom:26px;}
        .title h1{font-size:26px;font-weight:800;color:var(--blue);letter-spacing:.3px;}
        .title span{display:block;font-size:12px;color:var(--muted);font-weight:500;letter-spacing:2px;text-transform:uppercase;margin-top:4px;}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;background:var(--row);border-radius:10px;padding:18px 22px;margin-bottom:24px;}
        .info-grid .item{font-size:13.5px;display:flex;gap:8px;}
        .info-grid .item .k{font-weight:700;min-width:96px;}
        .info-grid .item .v{color:var(--ink);font-weight:500;}
        .statement{font-size:15px;line-height:2.1;background:#fff;border:1px dashed var(--line);border-radius:10px;padding:20px 22px;margin-bottom:26px;font-weight:500;}
        .statement b{color:var(--blue);font-weight:800;}
        table{width:100%;border-collapse:collapse;margin-bottom:6px;}
        thead th{background:var(--ink);color:#fff;font-weight:700;font-size:13px;text-align:right;padding:12px 14px;}
        tbody td{padding:13px 14px;font-size:13px;font-weight:500;}
        tbody tr:nth-child(even){background:var(--row);}
        tfoot td{padding:14px;font-weight:800;font-size:14.5px;background:var(--blue);color:#fff;}
        .amount-box{display:flex;justify-content:space-between;align-items:center;background:var(--blue);color:#fff;padding:16px 22px;border-radius:8px;margin-top:20px;}
        .amount-box .lbl{font-weight:700;font-size:16px;}
        .amount-box .amt{font-weight:800;font-size:20px;}
        .lower{display:flex;justify-content:space-between;align-items:flex-end;gap:30px;margin-top:34px;}
        .sign{font-size:13.5px;line-height:2;}
        .sign .k{font-weight:700;}
        .sign .line{margin-top:26px;border-top:1.5px dashed var(--muted);width:200px;padding-top:6px;color:var(--muted);font-size:12px;text-align:center;}
        .qr-wrap{text-align:center;}
        #qrcode{width:120px;height:120px;padding:8px;border:1px solid var(--line);border-radius:8px;background:#fff;}
        #qrcode svg{width:100%;height:100%;display:block;}
        .qr-wrap span{display:block;font-size:11px;color:var(--muted);margin-top:6px;font-weight:500;}
        .footer{margin-top:40px;border-top:3px solid var(--blue);padding-top:12px;font-size:12px;color:var(--muted);text-align:center;line-height:1.9;}
        .footer b{color:var(--ink);}
        .print-action{position:fixed;top:16px;left:16px;border:0;border-radius:8px;background:var(--blue);color:#fff;padding:10px 16px;font-weight:700;font-family:'Tajawal',sans-serif;cursor:pointer;box-shadow:0 10px 25px rgba(20,30,70,.18);}
        @page{size:A4 portrait;margin:9mm;}
        @media print{
            html,body{background:#fff;padding:0;margin:0;}
            .voucher{box-shadow:none;width:100%;max-width:100%;padding:0;transform-origin:top center;}
            .print-action{display:none;}
            table,.info-grid,.statement,.amount-box,.lower,.footer{page-break-inside:avoid;}
        }
    </style>
</head>
<body>
    <button class="print-action" onclick="window.print()">طباعة</button>

    <div class="voucher">
        <div class="head">
            <div class="brand"><img src="{{ $logo }}" alt="Samnan Water Solutions"></div>
            <div class="doc-meta">
                <div><b>رقم الإذن:</b> #{{ $payout->id }}</div>
                <div><b>تاريخ الطباعة:</b> {{ now()->format('Y-m-d h:i A') }}</div>
                <div><b>تاريخ الاعتماد:</b> {{ $payout->processed_at?->format('Y-m-d') ?: '-' }}</div>
            </div>
        </div>

        <div class="title">
            <h1>إذن صرف مستحقات فني</h1>
            <span>Technician Payout Voucher</span>
        </div>

        <div class="info-grid">
            <div class="item"><span class="k">اسم الفني:</span><span class="v">{{ $technicianName }}</span></div>
            <div class="item"><span class="k">هاتف الفني:</span><span class="v">{{ $technician?->phone ?: '-' }}</span></div>
            <div class="item"><span class="k">عدد الطلبات:</span><span class="v">{{ $payout->requests_count }}</span></div>
            <div class="item"><span class="k">حالة الطلب:</span><span class="v">معتمد</span></div>
        </div>

        <div class="statement">
            بعد مراجعة طلبات الصيانة الخاصة بالفني <b>{{ $technicianName }}</b>
            من تاريخ <b>{{ $fromDate?->format('Y-m-d') ?: '—' }}</b>
            إلى تاريخ <b>{{ $toDate?->format('Y-m-d') ?: '—' }}</b>،
            نفيدكم بأنه يستحق مبلغاً وقدره <b>{{ $amountFormatted }}</b> ريال فقط لا غير.
        </div>

        <table>
            <thead>
                <tr>
                    <th>م</th>
                    <th>رقم الطلب</th>
                    <th>نوع الصيانة</th>
                    <th>عدد الأجهزة</th>
                    <th>تاريخ الإكمال</th>
                    <th>المبلغ (ريال)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($earnings as $earning)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>#{{ $earning->maintenance_request_id }}</td>
                        <td>{{ $typeLabels[$earning->request_type] ?? $earning->request_type }}</td>
                        <td>{{ $earning->devices_count }}</td>
                        <td>{{ $earning->created_at?->format('Y-m-d') ?: '-' }}</td>
                        <td>{{ number_format((float) $earning->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">الإجمالي</td>
                    <td>{{ $amountFormatted }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="amount-box">
            <span class="lbl">المبلغ المستحق للصرف</span>
            <span class="amt">{{ $amountFormatted }} ريال</span>
        </div>

        <div class="lower">
            <div class="sign">
                <div><span class="k">اعتمد الطلب:</span> {{ $approverName }}</div>
                <div><span class="k">بتاريخ:</span> {{ $payout->processed_at?->format('Y-m-d h:i A') ?: '-' }}</div>
                <div class="line">التوقيع</div>
            </div>
            <div class="qr-wrap">
                <div id="qrcode"></div>
                <span>امسح لعرض الطلب على النظام</span>
            </div>
        </div>

        <div class="footer">
            <div><b>Samnan Water Solutions</b> — سمنان لحلول المياه</div>
            <div>الرقم الضريبي: 310093763800003 &nbsp;|&nbsp; 920022255 &nbsp;|&nbsp; solution@samnanstore.com.sa</div>
        </div>
    </div>

    <script>
        try {
            var qr = qrcode(0, 'M');
            qr.addData(@json($dashboardUrl));
            qr.make();
            document.getElementById('qrcode').innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0 });
        } catch (e) {
            document.getElementById('qrcode').textContent = 'QR';
        }

        // Scale the voucher down so all content fits on a single printed page.
        (function () {
            var el = document.querySelector('.voucher');
            // A4 portrait usable area @96dpi minus @page margins (9mm each side).
            var availableHeight = 1123 - Math.round((9 * 2) * 3.7795);

            function fitToPage() {
                el.style.zoom = '';
                var height = el.offsetHeight;
                if (height > availableHeight) {
                    el.style.zoom = availableHeight / height;
                }
            }
            function reset() {
                el.style.zoom = '';
            }

            window.addEventListener('beforeprint', fitToPage);
            window.addEventListener('afterprint', reset);
        })();
    </script>
</body>
</html>
