<div class="{{ $viewClass['form-group'] }} ajax-image-compact-field {!! !$errors->has($errorKey) ? '' : 'has-error' !!}" id="{{ $wrapId }}">

    <label class="{{ $viewClass['label'] }} control-label">{{ $label }}</label>

    <div class="{{ $viewClass['field'] }}">

        @include('admin::form.error')

        <input type="hidden" name="{{ $hiddenName }}" id="{{ $id }}" value="{{ e($hiddenValue) }}" />

        <input type="file" id="{{ $fileInputId }}" accept="image/*" class="ajax-image-file-input hide" tabindex="-1" aria-hidden="true" />

        <div class="ajax-image-filament ajax-image-filament--compact">
            <div class="ajax-image-compact-row">
                <div class="ajax-image-filament-zone" role="button" tabindex="0" aria-label="{{ __('Upload image') }}">

                    <div class="ajax-image-drop-pitch {{ $previewUrl ? 'hide' : '' }}">
                        <div class="ajax-image-drop-icon">
                            <i class="fa fa-cloud-upload"></i>
                        </div>
                        <div class="ajax-image-drop-text">
                            <span class="ajax-image-drop-title">{{ __('Click or drop') }}</span>
                            <span class="ajax-image-drop-sub">{{ __('max 12MB') }}</span>
                        </div>
                    </div>

                    <div class="ajax-image-preview-panel {{ $previewUrl ? '' : 'hide' }}">
                        <img id="{{ $id }}_preview"
                             src="{{ $previewUrl ? e($previewUrl) : '' }}"
                             alt=""
                             class="ajax-image-preview-img" />
                        <button type="button" class="ajax-image-fab-remove ajax-image-remove-btn" title="{{ __('Remove') }}">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>

                    <div class="ajax-image-progress-wrap hide">
                        <div class="ajax-image-progress-track">
                            <div class="ajax-image-progress-bar"></div>
                        </div>
                        <span class="ajax-image-progress-label">0%</span>
                    </div>
                </div>

                <div class="ajax-image-compact-aside">
                    <code class="ajax-image-path-code" title="{{ $hiddenValue ? e($hiddenValue) : '' }}">{{ $hiddenValue ? e($hiddenValue) : '—' }}</code>
                </div>
            </div>
        </div>

        @include('admin::form.help-block')

    </div>
</div>
