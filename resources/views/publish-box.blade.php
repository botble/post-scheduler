<div class="row form-group @if ($errors->has('publish_date')) has-error @endif">
    <label for="publish_date" class="control-label col-sm-3 text-right">{{ __('Date') }}</label>
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
    <label for="publish_time" class="control-label col-sm-3 text-right">{{ __('Time') }}</label>
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

@if ($data && $data->status != \Botble\Base\Enums\BaseStatusEnum::PUBLISHED)
    <div class="form-group">
        {!! Form::onOff('update_time_to_current', 0) !!}
        <label class="control-label">{{ __('Update published time to current time') }}</label>
    </div>
@endif
