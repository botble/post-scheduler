<?php

namespace Botble\PostScheduler\Providers;

use Botble\Base\Facades\Assets;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Facades\MetaBox;
use Botble\PostScheduler\Facades\PostScheduler;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if (! is_plugin_active('blog')) {
            return;
        }

        add_action(BASE_ACTION_META_BOXES, [$this, 'addPublishBox'], 40, 2);

        add_action(BASE_ACTION_AFTER_CREATE_CONTENT, [$this, 'saveSchedulerData'], 127, 3);
        add_action(BASE_ACTION_AFTER_UPDATE_CONTENT, [$this, 'saveSchedulerData'], 127, 3);

        add_filter(BASE_FILTER_BEFORE_GET_FRONT_PAGE_ITEM, [$this, 'checkPublishDateBeforeShow'], 128, 2);
        add_filter(BASE_FILTER_BEFORE_GET_SINGLE, [$this, 'checkPublishDateBeforeShowSingle'], 129, 2);
        add_filter('model_after_execute_get', [$this, 'checkPublishDateBeforeShow'], 129, 3);
    }

    public function addPublishBox(string $priority, $object): void
    {
        if (PostScheduler::isSupportedModule($object::class) && $priority == 'top') {
            Assets::addScripts(['timepicker'])
                ->addStyles(['timepicker']);

            MetaBox::addMetaBox(
                'publish_box_wrap',
                trans('plugins/post-scheduler::post-scheduler.publish_date'),
                [$this, 'addPublishFields'],
                $object::class,
                $priority,
            );
        }
    }

    public function addPublishFields(): string
    {
        $publishDate = Carbon::now(config('app.timezone'))->format(BaseHelper::getDateFormat());
        $publishTime = Carbon::now(config('app.timezone'))->format('H:i');

        $args = func_get_args();
        $data = $args[0];
        if ($data && $data->id) {
            $publishDate = BaseHelper::formatDate($data->created_at);
            $publishTime = BaseHelper::formatDate($data->created_at, 'H:i');
        }

        return view('plugins/post-scheduler::publish-box', compact('publishDate', 'publishTime', 'data'))->render();
    }

    public function saveSchedulerData($screen, $request, $object): void
    {
        if (! PostScheduler::isSupportedModule($object::class)) {
            return;
        }

        if ($request->input('update_time_to_current')) {
            $object->created_at = Carbon::now();
            $object->save();

            return;
        }

        $publishDate = $request->input('publish_date');

        if (empty($publishDate)) {
            return;
        }

        $dateTime = BaseHelper::parseDate($publishDate);

        if (! $dateTime) {
            return;
        }

        $publishTime = substr($request->input('publish_time') ?: '00:00', 0, 5);

        try {
            $dateTime->setTimeFromTimeString($publishTime);
        } catch (\Throwable) {
            $dateTime->setTime(0, 0);
        }

        $object->created_at = $dateTime->toDateTimeString();
        $object->saveQuietly();
    }

    public function checkPublishDateBeforeShowSingle($data, $model)
    {
        if (Auth::check()) {
            return $data;
        }

        return $this->checkPublishDateBeforeShow($data, $model);
    }

    public function checkPublishDateBeforeShow($data, $model)
    {
        if (! PostScheduler::isSupportedModule(get_class($model))) {
            return $data;
        }

        $now = Carbon::now(config('app.timezone'));

        // model_after_execute_get passes an already-executed Collection, where
        // mutating ->where() returns a new collection instead of modifying in
        // place. Filter and return explicitly so future-dated posts are hidden.
        if ($data instanceof Collection) {
            return $data
                ->filter(function ($item) use ($now) {
                    // When ->select() omits created_at, the attribute is absent
                    // on the model and we cannot decide. Preserve the row
                    // rather than silently dropping every result.
                    if (! array_key_exists('created_at', $item->getAttributes())) {
                        return true;
                    }

                    return $item->created_at && $item->created_at <= $now;
                })
                ->values();
        }

        $data->where($model->getTable() . '.created_at', '<=', $now->toDateTimeString());

        return $data;
    }
}
