@php
    $request = $invoice->maintenanceRequest;
    $customer = $request?->customer;
    $address = $request?->address;
    $items = collect();

    foreach ($invoice->services as $service) {
        $items->push([
            'description' => $service->name_en ?: $service->name_ar ?: 'Service',
            'price' => (float) ($service->price ?? 0),
            'quantity' => 1,
            'total' => (float) ($service->price ?? 0),
        ]);
    }

    foreach ($invoice->spareParts as $part) {
        $quantity = (float) ($part->pivot->quantity ?? 1);
        $price = (float) ($part->pivot->price ?? $part->price ?? 0);

        $items->push([
            'description' => $part->name_en ?: $part->name_ar ?: 'Spare Part',
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
        ]);
    }

    if ($items->isEmpty()) {
        $items->push([
            'description' => \App\Filament\Resources\SalesInvoiceResource::formatMaintenanceType($request?->type),
            'price' => (float) $invoice->total,
            'quantity' => 1,
            'total' => (float) $invoice->total,
        ]);
    }

    $sellerName = 'Samnan Water Solutions';
    $vatNumber = '310093763800003';
    $timestamp = $invoice->created_at?->toIso8601String() ?? now()->toIso8601String();
    $invoiceTotal = number_format((float) $invoice->total, 2, '.', '');
    $vatTotal = '0.00';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->id }} - Samnan Water Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js"></script>
    <style>
        :root{--blue:#20419C;--blue-dark:#19347D;--ink:#2B2F3A;--muted:#8A8F99;--row:#F4F5F7;--line:#E6E8EE;--page:#ECEEF1;}
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Poppins',sans-serif;background:var(--page);color:var(--ink);display:flex;justify-content:center;padding:40px 16px;-webkit-print-color-adjust:exact;print-color-adjust:exact;}
        .invoice{background:#fff;width:794px;max-width:100%;padding:54px 56px 40px;box-shadow:0 18px 50px rgba(20,30,70,.10);position:relative;}
        .logo{height:60px;margin-bottom:30px;display:flex;align-items:center;color:var(--blue);font-weight:800;font-size:28px;letter-spacing:.2px;}
        .logo span{display:block;font-size:12px;font-weight:600;color:var(--muted);letter-spacing:1.4px;text-transform:uppercase;margin-top:4px;}
        .bar-row{display:flex;align-items:center;gap:0;margin-bottom:42px;}
        .bar{height:34px;background:var(--blue);flex:1;}
        .bar.short{flex:0 0 56px;margin-left:14px;}
        .invoice-title{font-weight:800;font-size:46px;letter-spacing:1px;color:var(--ink);padding:0 18px;white-space:nowrap;}
        .meta{display:flex;justify-content:space-between;margin-bottom:34px;gap:32px;}
        .bill-to .label{font-weight:600;font-size:18px;margin-bottom:8px;}
        .bill-to .name{font-weight:700;font-size:15px;margin-bottom:5px;}
        .bill-to .addr{font-size:11.5px;color:var(--muted);line-height:1.7;font-weight:300;}
        .bill-to .phone{font-size:11.5px;color:var(--muted);margin-top:5px;font-weight:400;}
        .bill-to .phone b{color:var(--ink);font-weight:600;}
        .inv-info{font-size:13px;min-width:240px;}
        .inv-info .line{display:flex;margin-bottom:9px;}
        .inv-info .k{font-weight:700;width:118px;}
        .inv-info .v{color:var(--muted);font-weight:300;}
        table{width:100%;border-collapse:collapse;margin-bottom:6px;}
        thead th{background:var(--ink);color:#fff;font-weight:600;font-size:12.5px;text-align:left;padding:13px 16px;}
        tbody td{padding:15px 16px;font-size:12.5px;font-weight:400;}
        tbody tr:nth-child(even){background:var(--row);}
        tbody td.sl{font-weight:600;}
        tbody td.desc{font-weight:500;}
        .col-price,.col-qty,.col-total{width:115px;}
        .lower{display:flex;justify-content:space-between;margin-top:38px;gap:30px;}
        .lower-left{flex:1;}
        .zatca h4{font-size:13px;font-weight:700;margin-bottom:12px;}
        #qrcode{width:118px;height:118px;padding:8px;border:1px solid var(--line);border-radius:8px;background:#fff;}
        #qrcode svg{width:100%;height:100%;display:block;}
        .totals{min-width:270px;}
        .total-box{display:flex;justify-content:space-between;align-items:center;background:var(--blue);color:#fff;padding:13px 18px;border-radius:5px;margin-top:10px;}
        .total-box .lbl,.total-box .amt{font-weight:700;font-size:15px;}
        .footer{margin-top:54px;}
        .footer .divider{height:4px;background:var(--blue);border-radius:3px;margin-bottom:14px;}
        .footer .f-cols{display:flex;justify-content:space-between;align-items:flex-end;}
        .f-contact{font-size:12px;line-height:1.9;}
        .f-contact a{color:var(--ink);text-decoration:none;}
        .f-contact .vat b{font-weight:600;}
        .print-action{position:fixed;top:16px;right:16px;border:0;border-radius:8px;background:var(--blue);color:#fff;padding:10px 14px;font-weight:700;cursor:pointer;box-shadow:0 10px 25px rgba(20,30,70,.18);}
        @media print{body{background:#fff;padding:0;}.invoice{box-shadow:none;width:100%;padding:30px 34px;}.print-action{display:none;}}
    </style>
</head>
<body>
    <button class="print-action" onclick="window.print()">Print Invoice</button>

    <div class="invoice">
        <div class="logo">
            <div>Samnan<span>Water Solutions</span></div>
        </div>

        <div class="bar-row">
            <div class="bar"></div>
            <div class="invoice-title">INVOICE</div>
            <div class="bar short"></div>
        </div>

        <div class="meta">
            <div class="bill-to">
                <div class="label">Invoice to:</div>
                <div class="name">{{ trim(($customer?->first_name ?? '') . ' ' . ($customer?->last_name ?? '')) ?: '-' }}</div>
                <div class="addr">
                    {{ $address?->street ?: $address?->name ?: '-' }}<br>
                    {{ $address?->district?->name_en ?: $address?->district?->name_ar ?: '-' }}<br>
                    {{ $address?->city?->name_en ?: $address?->city?->name_ar ?: '-' }}
                </div>
                <div class="phone"><b>Phone:</b> {{ $customer?->phone ?: '-' }}</div>
                @if ($customer?->tax_number)
                    <div class="phone"><b>Tax Number:</b> {{ $customer->tax_number }}</div>
                @endif
            </div>
            <div class="inv-info">
                <div class="line"><span class="k">Invoice#</span><span class="v">{{ $invoice->id }}</span></div>
                <div class="line"><span class="k">Request#</span><span class="v">{{ $request?->id ?: '-' }}</span></div>
                <div class="line"><span class="k">Date</span><span class="v">{{ $invoice->created_at?->format('d / m / Y') }}</span></div>
                <div class="line"><span class="k">Payment</span><span class="v">{{ ucfirst($invoice->payment_method ?: '-') }} / {{ ucfirst($invoice->status ?: '-') }}</span></div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>SL.</th>
                    <th>Item Description</th>
                    <th class="col-price">Price</th>
                    <th class="col-qty">Qty.</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $item)
                    <tr>
                        <td class="sl">{{ $loop->iteration }}</td>
                        <td class="desc">{{ $item['description'] }}</td>
                        <td>SAR {{ number_format($item['price'], 2) }}</td>
                        <td>{{ rtrim(rtrim(number_format($item['quantity'], 2), '0'), '.') }}</td>
                        <td>SAR {{ number_format($item['total'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="lower">
            <div class="lower-left">
                <div class="zatca">
                    <h4>ZATCA QR Code</h4>
                    <div id="qrcode"></div>
                </div>
            </div>
            <div class="totals">
                <div class="total-box"><span class="lbl">Total:</span><span class="amt">SAR {{ number_format((float) $invoice->total, 2) }}</span></div>
            </div>
        </div>

        <div class="footer">
            <div class="divider"></div>
            <div class="f-cols">
                <div class="f-contact">
                    <div><a href="mailto:solution@samnanstore.com.sa">solution@samnanstore.com.sa</a></div>
                    <div class="vat"><b>VAT Registration Number:</b> 310093763800003</div>
                    <div><a href="tel:920022255">920022255</a></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function tlv(tag, value){
            var v = new TextEncoder().encode(value);
            var out = new Uint8Array(2 + v.length);
            out[0] = tag; out[1] = v.length; out.set(v, 2);
            return out;
        }
        function concat(arrs){
            var len = arrs.reduce(function(a,b){return a+b.length;},0);
            var out = new Uint8Array(len), off = 0;
            arrs.forEach(function(a){out.set(a,off); off += a.length;});
            return out;
        }
        function bytesToB64(bytes){
            var s = '';
            for (var i=0;i<bytes.length;i++) s += String.fromCharCode(bytes[i]);
            return btoa(s);
        }
        var payload = concat([
            tlv(1, @json($sellerName)),
            tlv(2, @json($vatNumber)),
            tlv(3, @json($timestamp)),
            tlv(4, @json($invoiceTotal)),
            tlv(5, @json($vatTotal))
        ]);
        var qrData = @json($invoice->qr_code) || bytesToB64(payload);
        try{
            var qr = qrcode(0, 'M');
            qr.addData(qrData);
            qr.make();
            document.getElementById('qrcode').innerHTML = qr.createSvgTag({cellSize:4, margin:0});
        }catch(e){
            document.getElementById('qrcode').textContent = 'QR';
        }
    </script>
</body>
</html>
