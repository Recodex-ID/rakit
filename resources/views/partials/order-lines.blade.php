{{-- Line editor shared by the purchase and sales order forms (App\Livewire\Concerns\ManagesOrderLines). --}}
<flux:card class="space-y-4">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">Lines</flux:heading>
        <flux:button type="button" variant="outline" size="sm" icon="plus" wire:click="addLine">Add line</flux:button>
    </div>

    <flux:error name="lines" />

    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-zinc-600">
                    <th scope="col" class="py-2 pe-3 font-medium">Item</th>
                    <th scope="col" class="w-28 px-3 py-2 font-medium">Quantity</th>
                    <th scope="col" class="w-44 px-3 py-2 font-medium">Unit price (Rp)</th>
                    <th scope="col" class="w-40 px-3 py-2 text-right font-medium">Subtotal</th>
                    <th scope="col" class="w-12 py-2 ps-3"><span class="sr-only">Remove</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @forelse ($lines as $index => $line)
                    <tr wire:key="line-{{ $index }}" class="align-top">
                        <td class="py-2 pe-3">
                            <flux:select wire:model.live="lines.{{ $index }}.item_id" placeholder="Choose an item" aria-label="Item on line {{ $index + 1 }}">
                                @foreach ($this->selectableItems as $item)
                                    <flux:select.option value="{{ $item->id }}">{{ $item->sku }} · {{ $item->name }} ({{ $item->unit }})</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="lines.{{ $index }}.item_id" />
                        </td>
                        <td class="px-3 py-2">
                            <flux:input type="number" min="1" step="1" wire:model.live.debounce.400ms="lines.{{ $index }}.quantity" aria-label="Quantity on line {{ $index + 1 }}" />
                            <flux:error name="lines.{{ $index }}.quantity" />
                        </td>
                        <td class="px-3 py-2">
                            <flux:input type="number" min="0" step="1" wire:model.live.debounce.400ms="lines.{{ $index }}.unit_price" aria-label="Unit price on line {{ $index + 1 }}" />
                            <flux:error name="lines.{{ $index }}.unit_price" />
                        </td>
                        <td class="px-3 py-2 pt-4 text-right tabular-nums">
                            {{ \App\Support\Money::format((int) $line['quantity'] * (int) $line['unit_price']) }}
                        </td>
                        <td class="py-2 ps-3">
                            <flux:button type="button" variant="outline" size="sm" icon="x-mark" wire:click="removeLine({{ $index }})" aria-label="Remove line {{ $index + 1 }}" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-zinc-600">No lines yet. Use "Add line" to add the first item.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="border-t border-zinc-200">
                    <td colspan="3" class="py-3 pe-3 text-right font-medium">Total</td>
                    <td class="px-3 py-3 text-right font-display text-lg font-bold tabular-nums">{{ \App\Support\Money::format($this->linesTotal()) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</flux:card>
