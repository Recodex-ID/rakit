@php use App\Support\Money; @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>{{ $order->number }} - {{ $company['name'] }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f4f4f5; color: #18181b; font: 14px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        .sheet { max-width: 210mm; margin: 24px auto; padding: 18mm 16mm; background: #fff; }
        .toolbar { max-width: 210mm; margin: 24px auto 0; display: flex; justify-content: flex-end; gap: 8px; }
        .toolbar button { font: inherit; padding: 8px 16px; border: 1px solid #18181b; background: #18181b; color: #fff; border-radius: 6px; cursor: pointer; }
        header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #18181b; padding-bottom: 16px; }
        h1 { margin: 0; font-size: 22px; }
        h2 { margin: 0 0 4px; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #52525b; }
        .muted { color: #52525b; }
        .mono { font-family: ui-monospace, "Cascadia Mono", Consolas, monospace; }
        .right { text-align: right; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 6px; border-bottom: 1px solid #e4e4e7; vertical-align: top; }
        th { text-align: left; font-size: 12px; color: #52525b; }
        tfoot td { border-bottom: 0; font-weight: 700; font-size: 16px; }
        .notes { margin-top: 20px; white-space: pre-line; }
        .signatures { display: grid; grid-template-columns: repeat(2, 1fr); gap: 48px; margin-top: 56px; }
        .signatures div { border-top: 1px solid #18181b; padding-top: 6px; text-align: center; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { margin: 0; padding: 0; max-width: none; }
            @page { size: A4; margin: 16mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print or save as PDF</button>
    </div>

    <main class="sheet">
        <header>
            <div>
                <h1>{{ $company['name'] }}</h1>
                @if ($company['address'])
                    <div class="muted" style="white-space: pre-line">{{ $company['address'] }}</div>
                @endif
                <div class="muted">
                    {{ collect([$company['phone'], $company['email']])->filter()->implode(' · ') }}
                </div>
                @if ($company['tax_id'])
                    <div class="muted">NPWP {{ $company['tax_id'] }}</div>
                @endif
            </div>
            <div class="right">
                <h2>{{ $heading }}</h2>
                <div class="mono" style="font-size: 18px; font-weight: 700">{{ $order->number }}</div>
                <div class="muted">{{ $order->order_date->translatedFormat('j F Y') }}</div>
                <div class="muted">Status: {{ $order->status->label() }}</div>
            </div>
        </header>

        <section class="grid">
            <div>
                <h2>{{ $partyLabel }}</h2>
                <strong>{{ $party->name }}</strong>
                @if ($party->address)
                    <div style="white-space: pre-line">{{ $party->address }}</div>
                @endif
                <div class="muted">{{ collect([$party->phone, $party->email])->filter()->implode(' · ') }}</div>
            </div>
            <div>
                <h2>{{ $warehouseLabel }}</h2>
                <strong>{{ $order->warehouse->name }}</strong>
                @if ($order->warehouse->address)
                    <div style="white-space: pre-line">{{ $order->warehouse->address }}</div>
                @endif
            </div>
            <div>
                <h2>Prepared by</h2>
                <div>{{ $order->creator?->name ?? '-' }}</div>
            </div>
        </section>

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th class="right">Quantity</th>
                    <th class="right">Unit price</th>
                    <th class="right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->lines as $line)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            {{ $line->item->name }}
                            <div class="mono muted" style="font-size: 12px">{{ $line->item->sku }}</div>
                        </td>
                        <td class="right">{{ number_format($line->quantity, 0, ',', '.') }} {{ $line->item->unit }}</td>
                        <td class="right">{{ Money::format($line->unit_price) }}</td>
                        <td class="right">{{ Money::format($line->subtotal()) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="right">Total</td>
                    <td class="right">{{ Money::format($order->total()) }}</td>
                </tr>
            </tfoot>
        </table>

        @if ($order->notes)
            <section class="notes">
                <h2>Notes</h2>
                {{ $order->notes }}
            </section>
        @endif

        <section class="signatures">
            <div>Prepared by</div>
            <div>{{ $partyLabel === 'Supplier' ? 'Approved by' : 'Received by' }}</div>
        </section>
    </main>
</body>
</html>
