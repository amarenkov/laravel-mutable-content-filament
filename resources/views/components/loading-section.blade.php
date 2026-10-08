<div
    role="status"
    aria-busy="true"
    class="fi-section fi-loading-section"
    style="height: {{ $height ?? '8rem' }}; display: flex; align-items: center; justify-content: center; gap: 0.5rem;"
>
    <x-filament::loading-indicator style="width: 1.5rem; height: 1.5rem; color: var(--primary-500);" />

    <span style="font-size: 0.875rem; color: var(--gray-500);">
        {{ $loadingLabel ?? __('mutable-content-filament::ui.loading') }}
    </span>
</div>
