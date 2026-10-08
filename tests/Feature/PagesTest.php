<?php

namespace Amarenkov\MutableContentFilament\Tests\Feature;

use Amarenkov\MutableContent\Models\Field\Field;
use Amarenkov\MutableContent\Models\Lov\Lov;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Fields\Resources\Usage\UsageResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\LovResource;
use Amarenkov\MutableContentFilament\Filament\Resources\Lovs\Resources\Items\ItemResource;

use Amarenkov\MutableContentFilament\Tests\TestCase;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\RecordResource;

class PagesTest extends TestCase
{
    public function test_management_pages_render(): void
    {
        $field = Field::where('code', 'quantity')->first();
        $lov = Lov::where('code', 'record_status')->first();

        $this->get(FieldResource::getUrl())->assertOk()->assertSee('System settings')->assertSee('Quantity');
        $this->get(UsageResource::getUrl('index', ['field' => $field->code()]))->assertOk()->assertSee('Usage of the Quantity field');
        $this->get(LovResource::getUrl())->assertOk()->assertSee('Record status');
        $this->get(ItemResource::getUrl('index', ['lov' => $lov->code()]))->assertOk()->assertSee('Items of the Record status LOV')->assertSee('Draft');
    }

    public function test_record_pages_render(): void
    {
        $this->get(RecordResource::getUrl())->assertOk()->assertSee('Quantity');
        $this->get(RecordResource::getUrl('create'))->assertOk()->assertSee('Due date');
    }
}
