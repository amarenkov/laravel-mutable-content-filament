<?php

namespace Amarenkov\MutableContentFilament\Filament\Resources\Fields\Pages;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Size;

use Amarenkov\MutableContent\Domain\LovRegistry;
use Amarenkov\MutableContent\Domain\MutableClassRegistry;

use Amarenkov\MutableContent\Models\Field\Field as FieldModel;
use Amarenkov\MutableContent\Models\Field\Usage as FieldUsageModels;
use Amarenkov\MutableContent\Models\Lov\Item as LovItem;

use Amarenkov\MutableContentFilament\Filament\Resources\Base\Pages\ManageRecords;

use Amarenkov\MutableContentFilament\Filament\Resources\Fields\FieldResource;

class ManageFields extends ManageRecords
{
    // const
    const LOV_TAB_PREFIX = 'lov-';

    // static
    protected static string $resource = FieldResource::class;

    // protected
    protected static function boundTo(Builder $query, string $mutableClass): Builder
    {
        return $query->whereHas('usages', function (Builder $query) use ($mutableClass) {
            $query->whereIn(FieldUsageModels::CODE_SCOPE, $mutableClass::getFieldScopes());
        });
    }

    protected static function boundToLov(Builder $query, string $lovCode): Builder
    {
        return $query->whereHas('usages', function (Builder $query) use ($lovCode) {
            $query->whereIn(FieldUsageModels::CODE_SCOPE, LovItem::getFieldScopesForLov($lovCode));
        });
    }

    protected function tabButton(string $key, Tab $tab): Action
    {
        return Action::make('tab_'.Str::slug($key, '_'))
            ->label($tab->getLabel())
            ->badge($tab->getBadge())
            ->size(Size::Small)
            ->color(fn () => $this->activeTab === $key ? 'primary' : 'gray')
            ->outlined(fn () => $this->activeTab !== $key)
            ->action(function () use ($key) {
                $this->activeTab = $key;
                $this->updatedActiveTab();
            });
    }

    // public
    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make(__('mutable-content-filament::ui.tabs.all'))
                ->badge(fn () => FieldModel::count()),
        ];

        $mcds = app(MutableClassRegistry::class)->all();
        usort($mcds, fn ($a, $b) => mb_strtolower($a->label) <=> mb_strtolower($b->label));

        foreach ($mcds as $mcd) {
            $mutableClass = $mcd->mutableClass;

            $tabs[Str::slug(str_replace('\\', '-', $mutableClass))] = Tab::make($mcd->label)
                ->modifyQueryUsing(fn (Builder $query) => static::boundTo($query, $mutableClass))
                ->badge(fn () => static::boundTo(FieldModel::query(), $mutableClass)->count());
        }

        $lovs = app(LovRegistry::class)->getLovsOptions() ?? [];
        uasort($lovs, fn ($a, $b) => mb_strtolower((string)$a) <=> mb_strtolower((string)$b));

        foreach ($lovs as $lovCode => $lovLabel) {
            $lovCode = (string)$lovCode;

            $tabs[self::LOV_TAB_PREFIX.Str::slug($lovCode)] = Tab::make((string)$lovLabel)
                ->modifyQueryUsing(fn (Builder $query) => static::boundToLov($query, $lovCode))
                ->badge(fn () => static::boundToLov(FieldModel::query(), $lovCode)->count());
        }

        $tabs['unbound'] = Tab::make(__('mutable-content-filament::ui.tabs.unbound'))
            ->modifyQueryUsing(fn (Builder $query) => $query->whereDoesntHave('usages'))
            ->badge(fn () => FieldModel::whereDoesntHave('usages')->count());

        return $tabs;
    }

    public function getTabsContentComponent(): Component
    {
        $common = [];
        $classes = [];
        $lovs = [];

        $filterTabLabels = [];

        foreach ($this->getCachedTabs() as $key => $tab) {
            $key = (string) $key;

            if (in_array($key, ['all', 'unbound'], true)) {
                $common[] = $this->tabButton($key, $tab);
            } elseif (str_starts_with($key, self::LOV_TAB_PREFIX)) {
                $lovs[] = $this->tabButton($key, $tab);
            } else {
                $classes[] = $this->tabButton($key, $tab);
            }

            if ($key !== 'all') {
                $filterTabLabels[$key] = $tab->getLabel();
            }
        }

        $activeFilterTabLabel = fn () => $filterTabLabels[(string) $this->activeTab] ?? null;

        return Group::make([
            Section::make(__('mutable-content-filament::ui.tabs.quick_filters'))
                ->description(fn () => ($label = $activeFilterTabLabel()) ? __('mutable-content-filament::ui.tabs.selected', ['label' => $label]) : null)
                ->schema([
                    Actions::make($common)
                        ->hidden(empty($common)),

                    Actions::make($classes)
                        ->label(__('mutable-content-filament::ui.tabs.classes'))
                        ->hidden(empty($classes)),

                    Actions::make($lovs)
                        ->label(__('mutable-content-filament::ui.lovs'))
                        ->hidden(empty($lovs)),
                ])
                ->collapsed(fn () => $activeFilterTabLabel() === null)
                ->compact(),
        ])
            ->key('resourceTabs');
    }
}
