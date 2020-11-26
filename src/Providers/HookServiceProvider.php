<?php

namespace Botble\PostScheduler\Providers;

use Assets;
use Auth;
use BaseHelper;
use Botble\Base\Models\BaseModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use MetaBox;
use PostScheduler;

class HookServiceProvider extends ServiceProvider
{
    public function boot()
    {
        add_action(BASE_ACTION_META_BOXES, [$this, 'addPublishBox'], 40, 2);

        add_action(BASE_ACTION_AFTER_CREATE_CONTENT, [$this, 'saveSchedulerData'], 127, 3);
        add_action(BASE_ACTION_AFTER_UPDATE_CONTENT, [$this, 'saveSchedulerData'], 127, 3);

        add_filter(BASE_FILTER_BEFORE_GET_FRONT_PAGE_ITEM, [$this, 'checkPublishDateBeforeShow'], 128, 2);
        add_filter(BASE_FILTER_BEFORE_GET_SINGLE, [$this, 'checkPublishDateBeforeShowSingle'], 129, 2);
    }

    /**
     * @param string $priority
     * @param BaseModel $object
     */
    public function addPublishBox(string $priority, $object)
    {
        if (PostScheduler::isSupportedModule(get_class($object)) && $priority == 'top') {
            Assets::addScripts(['timepicker'])
                ->addStyles(['timepicker']);

            MetaBox::addMetaBox('publish_box_wrap', trans('plugins/post-scheduler::post-scheduler.publish_date'), [$this, 'addPublishFields'], get_class($object), $priority, 'default');
        }
    }

    /**
     * @return string
     * @throws \Throwable
     */
    public function addPublishFields()
    {
        $publishDate = now(config('app.timezone'))->format(config('core.base.general.date_format.date'));
        $publishTime = now(config('app.timezone'))->format('G:i');

        $args = func_get_args();
        $data = $args[0];
        if ($data && $data->id) {
            $publishDate = BaseHelper::formatDate($data->created_at);
            $publishTime = BaseHelper::formatDate($data->created_at, 'G:i');
        }

        return view('plugins/post-scheduler::publish-box', compact('publishDate', 'publishTime', 'data'))->render();
    }

    /**
     * @param $screen
     * @param Request $request
     * @param \Eloquent $object
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function saveSchedulerData($screen, $request, $object)
    {
        if (PostScheduler::isSupportedModule(get_class($object))) {
            if ($request->input('update_time_to_current')) {
                $object->created_at = now();
                $object->save();
                return;
            }

            $publishDate = $request->input('publish_date');
            $publishTime = $request->input('publish_time', '00:00');
            if (!empty($publishDate)) {
                $object->created_at = Carbon::createFromFormat(config('core.base.general.date_format.date') . ' G:i', $publishDate . ' ' . $publishTime)->toDateTimeString();
                $object->save();
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $data
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return mixed
     */
    public function checkPublishDateBeforeShowSingle($data, $model)
    {
        if (Auth::check()) {
            return $data;
        }

        return $this->checkPublishDateBeforeShow($data, $model);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Builder $data
     * @param $screen
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return mixed
     */
    public function checkPublishDateBeforeShow($data, $model)
    {
        if (PostScheduler::isSupportedModule(get_class($model))) {
            $table = $model->getTable();
            $data->where($table . '.created_at', '<=', now(config('app.timezone'))->toDateTimeString());
        }

        return $data;
    }
}
