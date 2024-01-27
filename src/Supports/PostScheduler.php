<?php

namespace Botble\PostScheduler\Supports;

class PostScheduler
{
    public function registerModule(string|array $model): self
    {
        if (! is_array($model)) {
            $model = [$model];
        }
        config([
            'plugins.post-scheduler.general.supported' => array_merge(
                config('plugins.post-scheduler.general.supported', []),
                $model
            ),
        ]);

        return $this;
    }

    public function supportedModules(): array
    {
        return config('plugins.post-scheduler.general.supported', []) ?: [];
    }

    public function isSupportedModule(string $model): bool
    {
        return in_array($model, $this->supportedModules());
    }
}
