{{-- The three ways stock can change, keyed by the document that causes each. Drawn on a dark surface. --}}
<table {{ $attributes->merge(['class' => 'w-full text-sm']) }}>
    <thead>
        <tr class="border-b border-brand-graphite text-left text-xs text-brand-mist">
            <th class="pb-2 font-medium">Document</th>
            <th class="pb-2 font-medium">Movement</th>
            <th class="pb-2 text-right font-medium">Stock</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-brand-graphite">
        <tr>
            <td class="py-3 font-mono">PO-{{ now()->year }}-····</td>
            <td class="py-3 text-brand-mist">Purchase receipt</td>
            <td class="py-3 text-right font-mono font-bold text-brand-orange">+ in</td>
        </tr>
        <tr>
            <td class="py-3 font-mono">SO-{{ now()->year }}-····</td>
            <td class="py-3 text-brand-mist">Sales delivery</td>
            <td class="py-3 text-right font-mono font-bold text-brand-orange">&minus; out</td>
        </tr>
        <tr class="border-b border-brand-graphite">
            <td class="py-3 font-mono text-brand-mist">Manual</td>
            <td class="py-3 text-brand-mist">Adjustment</td>
            <td class="py-3 text-right font-mono font-bold text-brand-orange">&plusmn;</td>
        </tr>
    </tbody>
</table>
