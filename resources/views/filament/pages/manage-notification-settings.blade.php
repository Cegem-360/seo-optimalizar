<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="flex gap-4 mt-4">
            {{ $this->saveAction }}
            {{ $this->testNotificationAction }}
        </div>
    </form>
    
    <x-filament-actions::modals />
</x-filament-panels::page>