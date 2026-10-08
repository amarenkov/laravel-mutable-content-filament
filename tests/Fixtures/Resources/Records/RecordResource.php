<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Resource;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Models\Record;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\CreateRecordPage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\EditRecordPage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\ListRecordsPage;
use Amarenkov\MutableContentFilament\Tests\Fixtures\Resources\Records\Pages\ViewRecordPage;

class RecordResource extends Resource
{
    protected static ?string $model = Record::class;

    protected static ?string $slug = 'records';

    public static function getPages(): array
    {
        return [
            'index' => ListRecordsPage::route('/'),
            'create' => CreateRecordPage::route('/create'),
            'view' => ViewRecordPage::route('/{record}'),
            'edit' => EditRecordPage::route('/{record}/edit'),
        ];
    }
}
