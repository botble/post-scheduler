<?php

namespace Botble\PostScheduler\Facades;

use Botble\PostScheduler\Supports\PostScheduler as BasePostScheduler;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Botble\PostScheduler\Supports\PostScheduler registerModule(array|string $model)
 * @method static array supportedModules()
 * @method static bool isSupportedModule(string $model)
 *
 * @see \Botble\PostScheduler\Supports\PostScheduler
 */
class PostScheduler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BasePostScheduler::class;
    }
}
