<?php

namespace Amarenkov\MutableContentFilament\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Table;

use Amarenkov\MutableContent\Models\ModelWithFields;

use Amarenkov\MutableContent\Domain\Field\Lov\Type;

use Amarenkov\MutableContent\Attributes\Class\Label as ClassLabel;
use Amarenkov\MutableContent\Attributes\Field\Common\Code as FieldCode;
use Amarenkov\MutableContent\Attributes\Field\Common\IsSystem as FieldIsSystem;

use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Type as CFAType;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\Label as CFALabel;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsRequired as CFAIsRequired;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\IsImmutableForSystemObjects as CFAIsImmutableForSystemObjects;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\LovCode as CFALovCode;
use Amarenkov\MutableContent\Attributes\FieldAttr\Common\ObjectClass as CFAObjectClass;

use Amarenkov\MutableContentFilament\Tests\Fixtures\Lovs\RecordStatus;

#[Table('records')]
#[ClassLabel('Record')]
#[FieldCode]
#[FieldIsSystem]
class Record extends ModelWithFields
{
    // const
    #[CFAType(Type::TYPE_INT), CFALabel('Quantity'), CFAIsRequired, CFAIsImmutableForSystemObjects]
    public const FIELD_QUANTITY = 'quantity';

    #[CFAType(Type::TYPE_WEIGHT), CFALabel('Weight')]
    public const FIELD_WEIGHT = 'weight';

    #[CFAType(Type::TYPE_BOOL), CFALabel('Active')]
    public const FIELD_IS_ACTIVE = 'is_active';

    #[CFAType(Type::TYPE_DATE), CFALabel('Due date')]
    public const FIELD_DUE_DATE = 'due_date';

    #[CFAType(Type::TYPE_LOV_ITEM), CFALabel('Status'), CFALovCode(RecordStatus::CODE)]
    public const FIELD_STATUS = 'status';

    #[CFAType(Type::TYPE_OBJECT), CFALabel('Owner'), CFAObjectClass(Owner::class)]
    public const FIELD_OWNER_ID = 'owner_id';

    // static
    protected static array|bool|null $fieldDefinitions = null;
}
