<?php

namespace Amarenkov\MutableContentFilament\Helpers;

use Filament\Support\Enums\IconSize;
use Filament\Support\Icons\Heroicon;

use function Filament\Support\generate_icon_html;

/**
 * Icon field value: a Heroicon name as Filament writes it, "o-cube" (outlined) or "cube" (solid).
 */
class IconHelper
{
    // const
    public const OPTIONS_LIMIT = 50;

    // static
    /**
     * @return array<string>
     */
    public static function getValues(): array
    {
        return array_map(fn (Heroicon $icon) => $icon->value, Heroicon::cases());
    }

    public static function getIcon(mixed $value): ?Heroicon
    {
        return is_string($value) ? Heroicon::tryFrom($value) : null;
    }

    /**
     * Select options: name => icon and name markup. Case-insensitive search by name and enum case.
     *
     * @return array<string, string>
     */
    public static function getOptions(?string $search = null, int $limit = self::OPTIONS_LIMIT): array
    {
        $search = mb_strtolower(trim((string)$search));

        $result = [];

        foreach (Heroicon::cases() as $icon) {
            if ($search !== '' && !str_contains($icon->value, $search) && !str_contains(mb_strtolower($icon->name), $search)) {
                continue;
            }

            $result[$icon->value] = static::getOptionLabel($icon);

            if (count($result) >= $limit) {
                break;
            }
        }

        return $result;
    }

    public static function getOptionLabel(Heroicon $icon): string
    {
        $svg = generate_icon_html($icon, size: IconSize::Medium)?->toHtml();

        return '<span style="display: inline-flex; align-items: center; gap: 0.5rem;">'.$svg.e($icon->value).'</span>';
    }
}
