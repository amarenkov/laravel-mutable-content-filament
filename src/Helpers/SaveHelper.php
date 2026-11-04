<?php

namespace Amarenkov\MutableContentFilament\Helpers;

use Closure;
use DomainException;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;

use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

class SaveHelper
{
    /**
     * Run a save in its own transaction and show unique violations and DomainException as notifications instead of a 500 error.
     */
    public static function save(Closure $callback): mixed
    {
        try {
            return DB::transaction($callback);
        } catch (UniqueConstraintViolationException $exception) {
            Notification::make()
                ->danger()
                ->title(__('mutable-content-filament::ui.save.duplicate'))
                ->body(__('mutable-content-filament::ui.save.duplicate_body'))
                ->send();

            throw (new Halt())->rollBackDatabaseTransaction();
        } catch (DomainException $exception) {
            Notification::make()
                ->danger()
                ->title(__('mutable-content-filament::ui.save.failed'))
                ->body($exception->getMessage())
                ->send();

            throw (new Halt())->rollBackDatabaseTransaction();
        }
    }

    /**
     * Set the change log author and comment, for a model that has a change log.
     */
    public static function prepareLog(Model $record, string $comment): void
    {
        if (method_exists($record, 'withLogContext')) {
            $record->withLogContext($comment)->user(auth()->id());
        }
    }
}
