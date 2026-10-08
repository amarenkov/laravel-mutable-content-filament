<?php

namespace Amarenkov\MutableContentFilament;

use Illuminate\Support\ServiceProvider;

use Filament\Support\Icons\Heroicon;

use Amarenkov\MutableContent\Domain\LovRegistry;

use Amarenkov\MutableContent\Domain\Field\Lov\Type as DomainFieldType;

class MutableContentFilamentServiceProvider extends ServiceProvider
{
    // protected
    /**
     * @return array<string, Heroicon>
     */
    protected function fieldTypeIcons(): array
    {
        return [
            DomainFieldType::TYPE_UNDEFINED => Heroicon::OutlinedQuestionMarkCircle,
            DomainFieldType::TYPE_STRING => Heroicon::OutlinedPencil,
            DomainFieldType::TYPE_TEXT => Heroicon::OutlinedDocumentText,
            DomainFieldType::TYPE_BOOL => Heroicon::OutlinedCheckCircle,
            DomainFieldType::TYPE_INT => Heroicon::OutlinedHashtag,
            DomainFieldType::TYPE_FLOAT => Heroicon::OutlinedCalculator,
            DomainFieldType::TYPE_LOV => Heroicon::OutlinedBookOpen,
            DomainFieldType::TYPE_LOV_ITEM => Heroicon::OutlinedListBullet,
            DomainFieldType::TYPE_ADDRESS => Heroicon::OutlinedMapPin,
            DomainFieldType::TYPE_OBJECT => Heroicon::OutlinedLink,
            DomainFieldType::TYPE_WEIGHT => Heroicon::OutlinedScale,
            DomainFieldType::TYPE_DENSITY => Heroicon::OutlinedCube,
            DomainFieldType::TYPE_SURFACE_DENSITY => Heroicon::OutlinedSquare3Stack3d,
            DomainFieldType::TYPE_LENGTH => Heroicon::OutlinedArrowsRightLeft,
            DomainFieldType::TYPE_AREA => Heroicon::OutlinedSquare2Stack,
            DomainFieldType::TYPE_VOLUME => Heroicon::OutlinedCubeTransparent,
            DomainFieldType::TYPE_DATE => Heroicon::OutlinedCalendar,
            DomainFieldType::TYPE_ICON => Heroicon::OutlinedPhoto,
            DomainFieldType::TYPE_SYSTEM => Heroicon::OutlinedCog6Tooth,
        ];
    }

    // public
    public function register(): void
    {
        $this->callAfterResolving(LovRegistry::class, function (LovRegistry $lovRegistry) {
            $lovRegistry->addItemIcons(DomainFieldType::CLASS_CODE, array_map(fn (Heroicon $icon) => $icon->value, $this->fieldTypeIcons()));
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mutable-content-filament');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'mutable-content-filament');

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/mutable-content-filament'),
        ], 'mutable-content-filament-lang');
    }
}
