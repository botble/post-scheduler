<?php

namespace Botble\PostScheduler\Facades;

use Botble\PostScheduler\Supports\PostScheduler;
use Illuminate\Support\Facades\Facade;

class PostSchedulerFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return PostScheduler::class;
    }
}
