<?php

namespace Botble\PostScheduler\Facades;

use Botble\PostScheduler\Supports\PostScheduler;
use Illuminate\Support\Facades\Facade;

class PostSchedulerFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return PostScheduler::class;
    }
}
