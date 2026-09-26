{{--
    Printable trading document (invoice, credit note, quotation, order). Rendered by dompdf, so styles are inline CSS 2.1 and
    the font is DejaVu Sans for wide character coverage.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $title }} {{ $number }}</title>
    <style>
        @page { margin: 40mm 16mm 22mm 16mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #222; }
        h1 { font-size: 18pt; margin: 0 0 2mm 0; letter-spacing: 1px; }
        .muted { color: #666; }
        .header { position: fixed; top: -32mm; left: 0; right: 0; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8pt; color: #777; text-align: center; }
        table { width: 100%; border-collapse: collapse; }
        .parties td { vertical-align: top; width: 50%; padding: 0; }
        .meta td { padding: 1mm 0; }
        .lines { margin-top: 6mm; }
        .lines th { background: #f0f2f5; text-align: left; padding: 2mm; font-size: 8.5pt; border-bottom: 1px solid #ccc; }
        .lines td { padding: 2mm; border-bottom: 1px solid #eee; }
        .num, .lines th.num, .tax-summary th.num { text-align: right; white-space: nowrap; }
        .totals { width: 45%; margin-left: 55%; margin-top: 4mm; }
        .totals td { padding: 1.5mm 2mm; }
        .totals .grand td { font-weight: bold; font-size: 11pt; border-top: 1px solid #222; }
        .tax-summary { margin-top: 6mm; width: 60%; }
        .tax-summary th { text-align: left; }
        .tax-summary th, .tax-summary td { padding: 1.5mm 2mm; font-size: 8.5pt; border-bottom: 1px solid #eee; }
        .watermark { position: fixed; top: 90mm; left: 20mm; font-size: 80pt; color: rgba(200, 0, 0, 0.12); transform: rotate(-30deg); }
    </style>
</head>
<body>
    @unless ($isFinal)
        <div class="watermark">{{ __('DRAFT') }}</div>
    @endunless

    <div class="header">
        <table>
            <tr>
                <td style="vertical-align: top;">
                    @if ($logo = $company->logoDataUri())
                        <img src="{{ $logo }}" alt="" style="max-height: 16mm; max-width: 55mm; margin-bottom: 1mm;"><br>
                    @endif
                    <strong style="font-size: 12pt;">{{ $company->displayName() }}</strong><br>
                    <span class="muted">{!! nl2br(e($company->address)) !!}</span>
                </td>
                <td class="num muted" style="vertical-align: top;">
                    @if ($company->tax_number){{ __('Tax number') }}: {{ $company->tax_number }}<br>@endif
                    @if ($company->registration_number){{ __('Registration') }}: {{ $company->registration_number }}<br>@endif
                    @if ($company->phone){{ __('Tel') }} {{ $company->phone }}<br>@endif
                    @if ($company->mobile){{ __('Mobile') }} {{ $company->mobile }}<br>@endif
                    {{ $company->email }}@if ($company->email && $company->website)<br>@endif{{ $company->website }}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ $company->displayName() }} · {{ $title }} {{ $number }}
    </div>

    <table class="parties">
        <tr>
            <td>
                <h1>{{ $title }}</h1>
                <table class="meta">
                    <tr><td class="muted">{{ __('Number') }}</td><td>{{ $number }}</td></tr>
                    <tr><td class="muted">{{ __('Date') }}</td><td>{{ Formatter::date($date) }}</td></tr>
                    @if ($dueDate)
                        <tr><td class="muted">{{ $dueDateLabel ?? __('Due date') }}</td><td>{{ Formatter::date($dueDate) }}</td></tr>
                    @endif
                    @if ($reference)
                        <tr><td class="muted">{{ $referenceLabel ?? __('Credits invoice') }}</td><td>{{ $reference }}</td></tr>
                    @endif
                    <tr><td class="muted">{{ __('Currency') }}</td><td>{{ $currencyCode }}</td></tr>
                </table>
            </td>
            <td>
                <div class="muted">{{ $partyLabel ?? __('Bill to') }}</div>
                <strong>{{ $party->name }}</strong><br>
                {!! nl2br(e($party->address)) !!}<br>
                @if ($party->tax_number){{ __('Tax number') }}: {{ $party->tax_number }}@endif
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th class="num">{{ __('Qty') }}</th>
                <th class="num">{{ __('Unit price') }}</th>
                <th>{{ __('Tax') }}</th>
                <th class="num">{{ __('Net') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line->description }}</td>
                    <td class="num">{{ Formatter::number($line->quantity, floor((float) $line->quantity) == (float) $line->quantity ? 0 : 2) }}</td>
                    <td class="num">{{ Formatter::amount($line->unit_price, $currencyCode) }}</td>
                    <td>{{ $line->tax?->tax_code ?? '—' }}@if ($line->is_reverse_charge) ({{ __('reverse charge') }})@endif</td>
                    <td class="num">{{ Formatter::amount($line->subtotal, $currencyCode) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>{{ __('Total before tax') }}</td><td class="num">{{ Formatter::amount($document->subtotal, $currencyCode) }}</td></tr>
        <tr><td>{{ __('Tax') }}</td><td class="num">{{ Formatter::amount($document->tax_amount, $currencyCode) }}</td></tr>
        <tr class="grand"><td>{{ $totalLabel }}</td><td class="num">{{ Formatter::money($document->total_amount, $currencyCode) }}</td></tr>
    </table>

    @if ($taxSummary->isNotEmpty())
        <table class="tax-summary">
            <thead>
                <tr><th>{{ __('Tax code') }}</th><th class="num">{{ __('Taxable amount') }}</th><th class="num">{{ __('Tax') }}</th></tr>
            </thead>
            <tbody>
                @foreach ($taxSummary as $row)
                    <tr>
                        <td>{{ $row['code'] }} ({{ Formatter::percent($row['rate']) }}%)</td>
                        <td class="num">{{ Formatter::amount($row['base'], $currencyCode) }}</td>
                        <td class="num">{{ Formatter::amount($row['tax'], $currencyCode) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($notes)
        <p style="margin-top: 8mm;"><span class="muted">{{ __('Notes') }}:</span> {!! nl2br(e($notes)) !!}</p>
    @endif

    @if ($document->lines->contains('is_reverse_charge', true))
        <p class="muted">{{ __('Reverse charge: the customer accounts for the tax.') }}</p>
    @endif
</body>
</html>
