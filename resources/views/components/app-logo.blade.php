@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Awan Gym" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-12 items-center justify-center rounded-full bg-transparent">
            <x-app-logo-icon class="size-12 rounded-full object-cover" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Awan Gym" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-12 items-center justify-center rounded-full bg-transparent">
            <x-app-logo-icon class="size-12 rounded-full object-cover" />
        </x-slot>
    </flux:brand>
@endif
