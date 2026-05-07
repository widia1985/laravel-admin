<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Support\AdminUploadStorageUrl;
use Encore\Admin\Admin;
use Encore\Admin\Form\Field;

/**
 * Compact ajax image upload (Filament-inspired, low vertical space).
 */
class AjaxImage extends Field
{
    protected $rules = 'nullable|string|max:512';

    protected $view = '';

    /**
     * @var bool
     */
    protected static $sharedAssetsRegistered = false;

    public function prepare($value)
    {
        return $value;
    }

    protected function registerSharedAssets(): void
    {
        if (self::$sharedAssetsRegistered) {
            return;
        }
        self::$sharedAssetsRegistered = true;

        Admin::style(<<<'CSS'
/* Compact upload: thumbnail + path, minimal height */
.ajax-image-compact-field { margin-bottom: 12px !important; }
.ajax-image-filament--compact { max-width: 100%; }
.ajax-image-compact-row {
  display: flex;
  align-items: stretch;
  gap: 10px;
}
.ajax-image-filament--compact .ajax-image-filament-zone {
  position: relative;
  flex: 0 0 118px;
  width: 118px;
  height: 88px;
  border: 2px dashed #d1d5db;
  border-radius: 8px;
  background: #f9fafb;
  cursor: pointer;
  transition: border-color 0.15s ease, background-color 0.15s ease;
  overflow: hidden;
}
.ajax-image-filament--compact .ajax-image-filament-zone:hover {
  border-color: #9ca3af;
  background: #f3f4f6;
}
.ajax-image-filament--compact .ajax-image-filament-zone.is-dragover {
  border-color: #3c8dbc;
  background: #ebf6fb;
}
.ajax-image-filament--compact .ajax-image-filament-zone.is-busy {
  pointer-events: none;
  opacity: 0.9;
}
.ajax-image-filament--compact .ajax-image-drop-pitch {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 6px 4px;
  height: 100%;
  box-sizing: border-box;
}
.ajax-image-filament--compact .ajax-image-drop-icon {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #e5e7eb;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 4px;
}
.ajax-image-filament--compact .ajax-image-drop-icon .fa {
  font-size: 15px;
  color: #6b7280;
}
.ajax-image-filament--compact .ajax-image-drop-text {
  line-height: 1.2;
}
.ajax-image-filament--compact .ajax-image-drop-title {
  display: block;
  font-size: 10px;
  font-weight: 600;
  color: #374151;
}
.ajax-image-filament--compact .ajax-image-drop-sub {
  display: block;
  font-size: 9px;
  color: #9ca3af;
}
.ajax-image-filament--compact .ajax-image-preview-panel {
  position: relative;
  padding: 4px;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  background: #fff;
}
.ajax-image-filament--compact .ajax-image-preview-img {
  max-width: 100%;
  max-height: 80px;
  border-radius: 4px;
  vertical-align: middle;
}
.ajax-image-filament--compact .ajax-image-fab-remove {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 22px;
  height: 22px;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: rgba(17, 24, 39, 0.72);
  color: #fff;
  line-height: 22px;
  text-align: center;
  cursor: pointer;
  z-index: 2;
}
.ajax-image-filament--compact .ajax-image-fab-remove:hover {
  background: rgba(220, 53, 69, 0.95);
}
.ajax-image-filament--compact .ajax-image-fab-remove .fa { font-size: 11px; vertical-align: middle; }
.ajax-image-filament--compact .ajax-image-progress-wrap {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 4px 6px 5px;
  background: linear-gradient(to top, rgba(255,255,255,0.95), transparent);
}
.ajax-image-filament--compact .ajax-image-progress-track {
  height: 3px;
  border-radius: 999px;
  background: #e5e7eb;
  overflow: hidden;
}
.ajax-image-filament--compact .ajax-image-progress-bar {
  height: 100%;
  width: 0%;
  border-radius: 999px;
  background: #3c8dbc;
  transition: width 0.08s linear;
}
.ajax-image-filament--compact .ajax-image-progress-label {
  display: block;
  margin-top: 2px;
  font-size: 9px;
  font-weight: 600;
  color: #6b7280;
  text-align: center;
}
.ajax-image-compact-aside {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
}
.ajax-image-compact-aside .ajax-image-path-code {
  font-size: 10px;
  color: #4b5563;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  padding: 4px 8px;
  border-radius: 4px;
  display: block;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.35;
}
CSS
        );
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $this->addRequiredAttributeFromRules();
        $this->registerSharedAssets();

        $disk = config('admin.upload.disk');
        $hiddenName = $this->elementName ?: $this->formatName($this->column);
        $hiddenValue = old($hiddenName, $this->value());
        $previewUrl = ($hiddenValue && is_string($hiddenValue))
            ? AdminUploadStorageUrl::url($hiddenValue, $disk)
            : '';

        $uploadUrl = admin_url('api/image-upload');
        $deleteUrl = admin_url('api/image-delete');
        $fieldId = $this->id;
        $fileInputId = $fieldId.'_ajax_file';

        $uploadUrlJson = json_encode($uploadUrl);
        $deleteUrlJson = json_encode($deleteUrl);
        $fieldIdJson = json_encode($fieldId);
        $fileInputIdJson = json_encode($fileInputId);
        $msgBadTypeJson = json_encode(__('Please choose an image file.'));

        Admin::script(<<<JS
(function () {
  var uploadUrl = {$uploadUrlJson};
  var deleteUrl = {$deleteUrlJson};
  var fieldId = {$fieldIdJson};
  var fileInputId = {$fileInputIdJson};
  var msgBadType = {$msgBadTypeJson};
  var \$wrap = \$('#' + fieldId + '_ajax_wrap');
  var \$hidden = \$('#' + fieldId);
  var \$zone = \$wrap.find('.ajax-image-filament-zone');
  var \$file = \$('#' + fileInputId);
  var \$pv = \$('#' + fieldId + '_preview');
  var \$pitch = \$wrap.find('.ajax-image-drop-pitch');
  var \$panel = \$wrap.find('.ajax-image-preview-panel');
  var \$pathCode = \$wrap.find('.ajax-image-path-code');
  var \$progWrap = \$wrap.find('.ajax-image-progress-wrap');
  var \$progBar = \$wrap.find('.ajax-image-progress-bar');
  var \$progLabel = \$wrap.find('.ajax-image-progress-label');
  var dragCounter = 0;

  function setPathHint(path) {
    var t = path ? path : '—';
    \$pathCode.text(t).attr('title', path || '');
  }

  function showEmptyState() {
    \$pitch.removeClass('hide');
    \$panel.addClass('hide');
    \$pv.attr('src', '');
  }

  function showPreviewState(url) {
    \$pitch.addClass('hide');
    \$panel.removeClass('hide');
    \$pv.attr('src', url);
  }

  function setPreview(url, path) {
    if (path !== undefined && path !== null) {
      setPathHint(path);
    } else {
      setPathHint(\$hidden.val() || '');
    }
    if (!url) {
      showEmptyState();
      return;
    }
    showPreviewState(url);
  }

  function clearUi() {
    \$hidden.val('');
    setPreview('', '');
    \$file.val('');
    setProgress(0, false);
  }

  function setProgress(pct, visible) {
    pct = Math.max(0, Math.min(100, pct));
    \$progBar.css('width', pct + '%');
    \$progLabel.text(visible ? (pct + '%') : '0%');
    if (visible) {
      \$progWrap.removeClass('hide');
    } else {
      \$progWrap.addClass('hide');
    }
  }

  function setBusy(on) {
    \$zone.toggleClass('is-busy', !!on);
  }

  function uploadFile(file) {
    if (!file || !file.type || file.type.indexOf('image/') !== 0) {
      if (typeof toastr !== 'undefined') {
        toastr.error(msgBadType);
      }
      return;
    }
    var fd = new FormData();
    fd.append('file', file);
    fd.append('_token', LA.token);

    setBusy(true);
    setProgress(0, true);

    \$.ajax({
      url: uploadUrl,
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      xhr: function () {
        var xhr = \$.ajaxSettings.xhr();
        if (xhr.upload) {
          xhr.upload.addEventListener('progress', function (e) {
            if (e.lengthComputable) {
              var pct = Math.round((e.loaded / e.total) * 100);
              setProgress(pct, true);
            }
          }, false);
        }
        return xhr;
      },
      complete: function () {
        setBusy(false);
        setProgress(0, false);
        \$file.val('');
      },
      success: function (res) {
        if (res && res.ok && res.path) {
          \$hidden.val(res.path);
          setPreview(res.url || '', res.path);
          if (typeof toastr !== 'undefined') {
            toastr.success('Image uploaded');
          }
        } else {
          if (typeof toastr !== 'undefined') {
            toastr.error((res && res.message) ? res.message : 'Upload failed');
          }
        }
      },
      error: function (xhr) {
        var msg = 'Upload failed';
        try {
          var j = xhr.responseJSON;
          if (j && j.message) {
            msg = j.message;
          }
        } catch (e) {}
        if (typeof toastr !== 'undefined') {
          toastr.error(msg);
        }
      }
    });
  }

  \$zone.on('click', function (e) {
    if (\$(e.target).closest('.ajax-image-remove-btn').length) {
      return;
    }
    if (\$zone.hasClass('is-busy')) {
      return;
    }
    e.preventDefault();
    var el = \$file[0];
    if (!el) {
      return;
    }
    window.setTimeout(function () {
      el.click();
    }, 0);
  });

  \$zone.on('keydown', function (e) {
    if (e.keyCode === 13 || e.keyCode === 32) {
      e.preventDefault();
      if (\$zone.hasClass('is-busy')) {
        return;
      }
      var el = \$file[0];
      if (el) {
        window.setTimeout(function () {
          el.click();
        }, 0);
      }
    }
  });

  \$file.on('change', function () {
    if (!this.files || !this.files.length) {
      return;
    }
    uploadFile(this.files[0]);
  });

  \$zone.on('dragenter', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter++;
    \$zone.addClass('is-dragover');
  });
  \$zone.on('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter--;
    if (dragCounter <= 0) {
      dragCounter = 0;
      \$zone.removeClass('is-dragover');
    }
  });
  \$zone.on('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
  });
  \$zone.on('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter = 0;
    \$zone.removeClass('is-dragover');
    var dt = e.originalEvent && e.originalEvent.dataTransfer;
    if (!dt || !dt.files || !dt.files.length) {
      return;
    }
    uploadFile(dt.files[0]);
  });

  \$wrap.on('click', '.ajax-image-remove-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var path = (\$hidden.val() || '').trim();
    if (!path) {
      clearUi();
      return;
    }
    setBusy(true);
    \$.ajax({
      url: deleteUrl,
      type: 'POST',
      data: {_token: LA.token, path: path},
      complete: function () { setBusy(false); },
      success: function (res) {
        if (res && res.ok) {
          clearUi();
          if (typeof toastr !== 'undefined') {
            toastr.success('Image removed');
          }
        } else {
          if (typeof toastr !== 'undefined') {
            toastr.error((res && res.message) ? res.message : 'Delete failed');
          }
        }
      },
      error: function (xhr) {
        var msg = 'Delete failed';
        try {
          var j = xhr.responseJSON;
          if (j && j.message) {
            msg = j.message;
          }
        } catch (err) {}
        if (typeof toastr !== 'undefined') {
          toastr.error(msg);
        }
      }
    });
  });
})();
JS
        );

        return view('admin::form.ajax_image', array_merge($this->variables(), [
            'hiddenName' => $hiddenName,
            'hiddenValue' => $hiddenValue,
            'previewUrl' => $previewUrl,
            'fileInputId' => $fileInputId,
            'wrapId' => $fieldId.'_ajax_wrap',
        ]))->render();
    }
}
