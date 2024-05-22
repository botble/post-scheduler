<?php

namespace Botble\PostScheduler\Facades;

use Botble\PostScheduler\Supports\PostScheduler;
use Illuminate\Support\Facades\Facade;

/**
* @see \Botble\PostScheduler\Supports\PostScheduler
*/
class PostScheduler extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PostScheduler::class;
    }
}
