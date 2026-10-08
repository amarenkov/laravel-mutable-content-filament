<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Base\RelationManagers;

use Illuminate\Contracts\View\View;

use Filament\Resources\RelationManagers\RelationManager as BaseRelationManager;

/**
 * Relation manager that shows an icon and a loading label while a lazy tab loads.
 */
class RelationManager extends BaseRelationManager
{
    public function placeholder(): View
    {
        return view(
            'mutable-content-filament::components.loading-section',
            [
                'height' => $this->getPlaceholderHeight(),
                ...$this->getPlaceholderData(),
            ],
        );
    }
}
