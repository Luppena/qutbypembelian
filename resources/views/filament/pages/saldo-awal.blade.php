<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div style="display:flex; justify-content:flex-end; margin-top:8px;">
            <x-filament::button type="submit" color="danger">
                Simpan Saldo Awal
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
