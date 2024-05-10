<?php

namespace Botble\PostScheduler\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Botble\PostScheduler\Facades\PostScheduler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class PostSchedulerServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('blog')) {
            return;
        }

        $this->setNamespace('plugins/post-scheduler')
            ->loadAndPublishConfigurations(['general'])
            ->loadAndPublishTranslations()
            ->loadAndPublishViews();

        AliasLoader::getInstance()->alias('PostScheduler', PostScheduler::class);

        $this->app->booted(function () {
            $this->app->register(HookServiceProvider::class);
        });
    }
}
