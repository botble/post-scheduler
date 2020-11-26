<?php

namespace Botble\PostScheduler\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\PostScheduler\Facades\PostSchedulerFacade;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class PostSchedulerServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    /**
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function boot()
    {
        if (is_plugin_active('blog')) {
            $this->setNamespace('plugins/post-scheduler')
                ->loadAndPublishConfigurations(['general'])
                ->loadAndPublishTranslations()
                ->loadAndPublishViews();

            AliasLoader::getInstance()->alias('PostScheduler', PostSchedulerFacade::class);

            $this->app->booted(function () {
                $this->app->register(HookServiceProvider::class);
            });
        }
    }
}
