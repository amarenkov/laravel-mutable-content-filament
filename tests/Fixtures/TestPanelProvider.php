<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\UsageResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\LovResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\ItemResource;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\RecordResource;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->resources([
                FieldResource::class,
                UsageResource::class,
                LovResource::class,
                ItemResource::class,
                RecordResource::class,
            ]);
    }
}
