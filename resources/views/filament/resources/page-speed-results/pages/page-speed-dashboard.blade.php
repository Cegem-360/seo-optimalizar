<x-filament-panels::page>
    @if ($this->hasHeaderWidgets())
        <x-filament-widgets::widgets
            :widgets="$this->getHeaderWidgets()"
            :columns="$this->getHeaderWidgetsColumns()"
        />
    @endif
    
    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getWidgetsColumns()"
    />
</x-filament-panels::page>
