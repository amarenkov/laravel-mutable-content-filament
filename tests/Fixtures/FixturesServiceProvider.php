<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Lovs\RecordStatus;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Owner;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Record;

class FixturesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->make(MutableClassRegistry::class)
            ->add(Owner::class)
            ->add(Record::class);

        $this->app->make(LovRegistry::class)->addClass(RecordStatus::class);
    }
}
