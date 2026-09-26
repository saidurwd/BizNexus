{{--
    Printable delivery note (packing slip): what was shipped, without prices. Rendered by dompdf.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Delivery note') }} {{ $delivery->delivery_number }}</title>
    <style>
        @page { margin: 40mm 16mm 22mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #222; }
        h1 { font-size: 18pt; margin: 0 0 2mm 0; letter-spacing: 1px; }
        .muted { color: #666; }
        .header { position: fixed; top: -32mm; left: 0; right: 0; }
        table { width: 100%; border-collapse: collapse; }
        .parties td { vertical-align: top; width: 50%; padding: 0; }
        .meta td { padding: 1mm 0; }
        .lines { margin-top: 6mm; }
        .lines th { background: #f0f2f5; text-align: left; padding: 2mm; font-size: 8.5pt; border-bottom: 1px solid #ccc; }
        .lines td { padding: 2mm; border-bottom: 1px solid #eee; }
        .num { text-align: right; white-space: nowrap; }
        .signature { margin-top: 20mm; }
        .signature td { width: 50%; padding-top: 12mm; border-top: 1px solid #999; }
    </style>
</head>
<body>
    <div class="header">
        @if ($logo = $company->logoDataUri())
            <img src="{{ $logo }}" alt="" style="max-height: 16mm; max-width: 55mm; margin-bottom: 1mm;"><br>
        @endif
        <strong style="font-size: 12pt;">{{ $company->displayName() }}</strong><br>
        <span class="muted">{!! nl2br(e($company->address)) !!}</span>
    </div>

    <table class="parties">
        <tr>
            <td>
                <h1>{{ __('Delivery note') }}</h1>
                <table class="meta">
                    <tr><td class="muted">{{ __('Number') }}</td><td>{{ $delivery->delivery_number }}</td></tr>
                    <tr><td class="muted">{{ __('Date') }}</td><td>{{ Formatter::date($delivery->delivery_date) }}</td></tr>
                    <tr><td class="muted">{{ __('Sales order') }}</td><td>{{ $delivery->salesOrder?->order_number }}</td></tr>
                    @if ($delivery->salesOrder?->customer_reference)
                        <tr><td class="muted">{{ __('Your reference') }}</td><td>{{ $delivery->salesOrder->customer_reference }}</td></tr>
                    @endif
                    @if ($delivery->carrier || $delivery->tracking_number)
                        <tr><td class="muted">{{ __('Carrier') }}</td><td>{{ $delivery->carrier }} {{ $delivery->tracking_number }}</td></tr>
                    @endif
                </table>
            </td>
            <td>
                <div class="muted">{{ __('Deliver to') }}</div>
                <strong>{{ $delivery->customer?->name }}</strong><br>
                {!! nl2br(e($delivery->customer?->address)) !!}
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('SKU') }}</th>
                <th>{{ __('Description') }}</th>
                <th class="num">{{ __('Quantity') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($delivery->lines as $line)
                <tr>
                    <td>{{ $line->product?->sku }}</td>
                    <td>{{ $line->orderLine?->description }}</td>
                    <td class="num">{{ Formatter::quantity($line->quantity, $line->product?->unit?->decimals ?? 0) }} {{ $line->product?->unit?->code }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($delivery->notes)
        <p class="muted" style="margin-top: 6mm;">{!! nl2br(e($delivery->notes)) !!}</p>
    @endif

    <table class="signature">
        <tr>
            <td>{{ __('Received in good condition by') }}</td>
            <td style="padding-left: 10mm;">{{ __('Date') }}</td>
        </tr>
    </table>
</body>
</html>
