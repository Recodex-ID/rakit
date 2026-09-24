@props([
    'title',
    'description',
])

<div class="flex w-full flex-col">
    <flux:heading size="xl" class="font-display!">{{ $title }}</flux:heading>
    <flux:subheading class="mt-2">{{ $description }}</flux:subheading>
</div>
