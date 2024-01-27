@if(version_compare(get_core_version(), '7.0.0', '<'))
    <div class="row form-group @if ($errors->has('publish_date')) has-error @endif">
        <label for="publish_date" class="control-label col-sm-3 text-right">{{ trans('plugins/post-scheduler::post-scheduler.date') }}</label>
        <div class="col-sm-9">
            <div class="input-group date form_datetime form_datetime bs-datetime">
                {!! Form::text('publish_date', old('publish_date', $publishDate), ['class' => 'form-control datepicker', 'id' => 'publish_date', 'data-date-format' => config('core.base.general.date_format.js.date'),]) !!}
                <span class="input-group-prepend">
                <button class="btn default" type="button">
                    <span class="fa fa-fw fa-calendar"></span>
                </button>
            </span>
            </div>
        </div>
        {!! Form::error('publish_date', $errors) !!}
    </div>

    <div class="row form-group @if ($errors->has('publish_time')) has-error @endif">
        <label for="publish_time" class="control-label col-sm-3 text-right">{{ trans('plugins/post-scheduler::post-scheduler.time') }}</label>
        <div class="col-sm-9">
            <div class="input-group">
                {!! Form::text('publish_time', old('publish_time', $publishTime), ['class' => 'form-control time-picker timepicker timepicker-24', 'id' => 'publish_time']) !!}
                <span class="input-group-prepend">
                <button class="btn default" type="button">
                    <i class="fa fa-clock"></i>
                </button>
            </span>
            </div>
        </div>
        {!! Form::error('publish_time', $errors) !!}
    </div>

    @if ($data && $data->status != Botble\Base\Enums\BaseStatusEnum::PUBLISHED)
        <div class="form-group">
            {!! Form::onOff('update_time_to_current', 0) !!}
            <label class="control-label">{{ trans('plugins/post-scheduler::post-scheduler.update_publish_time_to_current_time') }}</label>
        </div>
    @endif
@else
    <div class="row mb-3">
        <label for="publish_date" class="control-label col-sm-3 text-right">{{ trans('plugins/post-scheduler::post-scheduler.date') }}</label>
        <div class="col-sm-9">
            {!! Form::datePicker('publish_date', old('publish_date', $publishDate)) !!}
        </div>
    </div>

    <div class="row mb-3">
        <label for="publish_time" class="control-label col-sm-3 text-right">{{ trans('plugins/post-scheduler::post-scheduler.time') }}</label>
        <div class="col-sm-9">
            {!! Form::time('publish_time', old('publish_time', $publishTime), ['class' => 'form-control']) !!}
        </div>
    </div>

    @if ($data && $data->status != Botble\Base\Enums\BaseStatusEnum::PUBLISHED)
        <div class="mb-3">
            <label class="form-label">{{ trans('plugins/post-scheduler::post-scheduler.update_publish_time_to_current_time') }}</label>
            {!! Form::onOff('update_time_to_current', 0, trans('plugins/post-scheduler::post-scheduler.update_publish_time_to_current_time')) !!}
        </div>
    @endif
@endif
