<div class="{{ $viewClass['form-group'] }} ajax-image-list-field {!! !$errors->has($errorKey) ? '' : 'has-error' !!}" id="{{ $wrapId }}">

    <label class="{{ $viewClass['label'] }} control-label">{{ $label }}</label>

    <div class="{{ $viewClass['field'] }}">

        @include('admin::form.error')

        @foreach ($slots as $s)
            <input type="hidden" name="{{ $s['name'] }}" id="{{ $s['inputId'] }}" value="{{ e($s['path']) }}" class="ajax-image-list-hidden" />
        @endforeach

        <input type="file" id="{{ $fileInputId }}" accept="image/*" multiple class="hide" tabindex="-1" aria-hidden="true" />

        <div class="ajax-image-list-stack">
            <div class="ajax-image-list-items"></div>

            <div class="ajax-image-list-add">
                <div class="ajax-image-filament-zone" role="button" tabindex="0" aria-label="{{ trans('admin.ajax_image_add_title') }}">
                    <div class="ajax-image-drop-pitch">
                        <div class="ajax-image-drop-icon" aria-hidden="true">
                            <i class="fa fa-cloud-upload"></i>
                        </div>
                        <div class="ajax-image-drop-text">
                            <span class="ajax-image-drop-title">{{ trans('admin.ajax_image_add_title') }}</span>
                            <span class="ajax-image-drop-sub">{{ trans('admin.ajax_image_add_hint', ['max' => $max]) }}</span>
                        </div>
                    </div>
                    <div class="ajax-image-progress-wrap hide">
                        <div class="ajax-image-progress-track">
                            <div class="ajax-image-progress-bar"></div>
                        </div>
                        <span class="ajax-image-progress-label">0%</span>
                    </div>
                </div>
            </div>
        </div>

        @include('admin::form.help-block')

    </div>
</div>
