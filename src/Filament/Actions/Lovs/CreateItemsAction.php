<?php

namespace Amarenkov\MutableContentFilament\Filament\Actions\Lovs;

use Closure;

use LogicException;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

use Amarenkov\MutableContent\Models\Lov\Lov as LovModel;

use Amarenkov\MutableContentFilament\Helpers\RequestHelper;

class CreateItemsAction extends Action
{
    // const
    const FIELD_LABELS = 'labels';

    // static
    public static function getDefaultName(): ?string
    {
        return 'createItems';
    }

    // protected
    protected LovModel|Closure|null $lov = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('mutable-content-filament::ui.create_items.label'))
            ->icon(Heroicon::OutlinedQueueList)
            ->modalHeading(__('mutable-content-filament::ui.create_items.heading'))
            ->modalSubmitActionLabel(__('mutable-content-filament::ui.create_items.submit'))
            ->schema([
                Textarea::make(self::FIELD_LABELS)
                    ->label(__('mutable-content-filament::ui.create_items.labels'))
                    ->helperText(__('mutable-content-filament::ui.create_items.labels_helper'))
                    ->rows(15)
                    ->required(),
            ])
            ->action(function (array $data) {
                $labels = preg_split('/\R/u', $data[self::FIELD_LABELS]);

                $result = $this->getLov()->createItemsFromLabels(
                    $labels,
                    RequestHelper::getPath().'/create-items',
                    auth()->id()
                );

                $this->sendResultNotification($result);
            });
    }

    protected function sendResultNotification(array $result): void
    {
        $created = count($result['created']);
        $restored = count($result['restored']);
        $skipped = count($result['skipped']);

        $body = [];

        if ($created) {
            $body[] = __('mutable-content-filament::ui.create_items.created', ['count' => $created]);
        }

        if ($restored) {
            $body[] = __('mutable-content-filament::ui.create_items.restored', ['count' => $restored]);
        }

        if ($skipped) {
            $body[] = __('mutable-content-filament::ui.create_items.skipped', ['count' => $skipped, 'labels' => implode(', ', $result['skipped'])]);
        }

        $isSomethingDone = $created || $restored;

        $notification = Notification::make()
            ->title($isSomethingDone ? __('mutable-content-filament::ui.create_items.done') : __('mutable-content-filament::ui.create_items.nothing'))
            ->body(implode(' ', $body));

        $isSomethingDone ? $notification->success() : $notification->warning();

        $notification->send();
    }

    // public
    public function lov(LovModel|Closure|null $lov): static
    {
        $this->lov = $lov;

        return $this;
    }

    public function getLov(): LovModel
    {
        $lov = $this->evaluate($this->lov);

        if (!$lov) {
            $livewire = $this->getLivewire();

            if ($livewire && method_exists($livewire, 'getParentRecord')) {
                $lov = $livewire->getParentRecord();
            }
        }

        if (!$lov instanceof LovModel) {
            throw new LogicException(static::class.' requires a lov: set it with lov() or use the action on a page with a lov parent record.');
        }

        return $lov;
    }
}
