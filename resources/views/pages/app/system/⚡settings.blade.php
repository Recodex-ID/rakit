<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Settings')] class extends Component
{
    public string $companyName = '';

    public string $companyAddress = '';

    public string $companyPhone = '';

    public string $companyEmail = '';

    public string $companyTaxId = '';

    public function mount(): void
    {
        $this->companyName = Setting::get('company_name', '') ?? '';
        $this->companyAddress = Setting::get('company_address', '') ?? '';
        $this->companyPhone = Setting::get('company_phone', '') ?? '';
        $this->companyEmail = Setting::get('company_email', '') ?? '';
        $this->companyTaxId = Setting::get('company_tax_id', '') ?? '';
    }

    public function save(): void
    {
        $this->validate([
            'companyName' => ['nullable', 'string', 'max:255'],
            'companyAddress' => ['nullable', 'string', 'max:500'],
            'companyPhone' => ['nullable', 'string', 'max:64'],
            'companyEmail' => ['nullable', 'email', 'max:255'],
            'companyTaxId' => ['nullable', 'string', 'max:64'],
        ]);

        Setting::put('company_name', $this->companyName);
        Setting::put('company_address', $this->companyAddress);
        Setting::put('company_phone', $this->companyPhone);
        Setting::put('company_email', $this->companyEmail);
        Setting::put('company_tax_id', $this->companyTaxId);

        Flux::toast(variant: 'success', text: 'Settings updated.');
    }
};
?>

<div class="w-full max-w-3xl space-y-6">
    <div>
        <flux:heading size="xl">Settings</flux:heading>
        <flux:subheading>Company details printed on purchase and sales orders.</flux:subheading>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-4">
            <flux:input wire:model="companyName" label="Company name" description="Falls back to the app name ({{ config('app.name') }}) when blank." />
            <flux:textarea wire:model="companyAddress" label="Address" rows="3" />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <flux:input wire:model="companyPhone" label="Phone" placeholder="+62 21 0000 0000" />
                <flux:input wire:model="companyEmail" type="email" label="Email" placeholder="purchasing@example.com" />
            </div>

            <flux:input wire:model="companyTaxId" label="Tax ID (NPWP)" description="Printed under the company name. Leave blank to hide it." />
        </flux:card>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary">Save</flux:button>
        </div>
    </form>
</div>
