<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Support\AdminUploadStorageUrl;
use Encore\Admin\Admin;
use Encore\Admin\Form\Field;

/**
 * One upload zone per group; paths map to multiple model columns (compact order).
 */
class AjaxImageList extends Field
{
    protected $view = '';

    protected $rules = [];

    protected static $sharedListUiRegistered = false;

    /**
     * Parent array-column validation builds broken rule keys; skip (paths are server-sanitized on delete).
     */
    public function getValidator(array $input)
    {
        return false;
    }

    /**
     * Parent uses Str::contains() on $column (mb_strpos); skip for array columns.
     */
    protected function formatColumn($column = '')
    {
        if (is_array($column)) {
            return $column;
        }

        return parent::formatColumn($column);
    }

    public function __construct($column, $arguments = [])
    {
        parent::__construct($column, $arguments);
        if (!is_array($this->column) || $this->column === []) {
            throw new \InvalidArgumentException('ajaxImageList requires a non-empty array of column names.');
        }
        $this->id = 'ail_'.substr(md5(implode('|', array_values($this->column))), 0, 14);
    }

    /**
     * @param  mixed  $value  string (insert per column) or array (update)
     * @return array|string
     */
    public function prepare($value)
    {
        if (!is_array($value)) {
            return is_string($value) ? trim($value) : $value;
        }
        $out = [];
        foreach ($this->column as $key => $col) {
            $v = array_key_exists($key, $value) ? $value[$key] : '';
            $out[$key] = is_string($v) ? trim($v) : '';
        }

        return $out;
    }

    /**
     * CSS + lightbox modal shared by AjaxImageList and AjaxImageJsonList.
     */
    public static function registerSharedListUiAssets(): void
    {
        if (self::$sharedListUiRegistered) {
            return;
        }
        self::$sharedListUiRegistered = true;

        $modalHtml = '<div class="modal fade" id="arex-ajax-img-lightbox" tabindex="-1" role="dialog" aria-hidden="true">'
                .'<div class="modal-dialog modal-lg" style="max-width:96vw;width:auto;margin:24px auto;">'
                .'<div class="modal-content">'
                .'<div class="modal-header" style="padding:10px 15px;">'
                .'<button type="button" class="close" data-dismiss="modal" aria-label="'.e(__('Close')).'"><span aria-hidden="true">&times;</span></button>'
                .'<h4 class="modal-title arex-ajax-img-lightbox-title" style="font-size:15px;">'.e(__('Preview')).'</h4>'
                .'</div>'
                .'<div class="modal-body text-center" style="padding:12px;">'
                .'<img id="arex-ajax-img-lightbox-img" src="" alt="" style="max-width:100%;max-height:78vh;border-radius:6px;" />'
                .'</div>'
                .'<div class="modal-footer" style="padding:10px 15px;">'
                .'<a id="arex-ajax-img-lightbox-dl" href="#" class="btn btn-primary btn-sm" target="_blank" download>'
                .'<i class="fa fa-download"></i> '.e(__('Download')).'</a> '
                .'<button type="button" class="btn btn-default btn-sm" data-dismiss="modal">'.e(__('Close')).'</button>'
                .'</div></div></div></div>';
            $modalJson = json_encode($modalHtml, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS);
            Admin::script(
                <<<'EOT'
$(function () {
  if ($('#arex-ajax-img-lightbox').length) { return; }
  $('body').append(
EOT
                .$modalJson
                .<<<'EOT'
);
  $(document).on('hidden.bs.modal', '#arex-ajax-img-lightbox', function () {
    $('#arex-ajax-img-lightbox-img').attr('src', '');
  });
});
EOT
            );

        Admin::style(<<<'CSS'
.ajax-image-list-field { margin-bottom: 14px !important; }
.ajax-image-list-stack {
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  gap: 10px;
}
.ajax-image-list-items {
  flex: 1 1 0;
  min-width: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.ajax-image-list-item {
  position: relative;
  width: 92px;
  height: 72px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  background: #f9fafb;
  flex-shrink: 0;
}
.ajax-image-list-item img {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  z-index: 0;
  pointer-events: none;
}
.ajax-image-list-item .ajax-image-list-remove {
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
.ajax-image-list-item .ajax-image-list-remove:hover { background: rgba(220, 53, 69, 0.95); }
.ajax-image-list-item .ajax-image-list-remove .fa { font-size: 11px; vertical-align: middle; }
.ajax-image-list-item.ajax-image-list-item--has-img { cursor: zoom-in; }
.ajax-image-list-item .ajax-image-list-dl {
  position: absolute;
  bottom: 2px;
  left: 2px;
  width: 22px;
  height: 22px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  text-align: center;
  text-decoration: none;
  border-radius: 999px;
  background: rgba(17, 24, 39, 0.72);
  color: #fff;
  z-index: 2;
  box-sizing: border-box;
}
.ajax-image-list-item .ajax-image-list-dl:hover { background: rgba(60, 141, 188, 0.95); color: #fff; }
.ajax-image-list-item .ajax-image-list-dl .fa { font-size: 10px; line-height: 1; }
#arex-ajax-img-lightbox .modal-body { background: #111827; }
#arex-ajax-img-lightbox .modal-content { border: none; border-radius: 8px; overflow: hidden; }
.ajax-image-list-add {
  flex: 0 0 auto;
  width: 100px;
  max-width: 100px;
}
.ajax-image-list-add .ajax-image-filament-zone {
  position: relative;
  width: 100px;
  min-height: 72px;
  height: 72px;
  border: 1px dashed #c8d0dc;
  border-radius: 10px;
  background: linear-gradient(180deg, #fbfcfd 0%, #f3f5f7 100%);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.85);
  cursor: pointer;
  transition: border-color 0.18s ease, background 0.18s ease, box-shadow 0.18s ease;
  overflow: hidden;
}
.ajax-image-list-add .ajax-image-filament-zone:hover {
  border-color: #3c8dbc;
  background: linear-gradient(180deg, #f5fafc 0%, #e8f4f9 100%);
  box-shadow: inset 0 1px 0 rgba(255,255,255,0.9), 0 1px 2px rgba(60, 141, 188, 0.08);
}
.ajax-image-list-add .ajax-image-filament-zone:focus {
  outline: none;
}
.ajax-image-list-add .ajax-image-filament-zone:focus-visible {
  outline: 2px solid #3c8dbc;
  outline-offset: 2px;
}
.ajax-image-list-add .ajax-image-filament-zone.is-dragover {
  border-color: #2a6f8f;
  border-style: solid;
  background: #e3f2f8;
  box-shadow: inset 0 0 0 1px rgba(60, 141, 188, 0.25);
}
.ajax-image-list-add .ajax-image-filament-zone.is-busy {
  pointer-events: none;
  opacity: 0.92;
}
.ajax-image-list-add .ajax-image-drop-pitch {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 4px;
  text-align: center;
  padding: 6px 4px;
  height: 100%;
  box-sizing: border-box;
}
.ajax-image-list-add .ajax-image-drop-icon {
  width: 30px;
  height: 30px;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}
.ajax-image-list-add .ajax-image-drop-icon .fa { font-size: 15px; color: #3c8dbc; }
.ajax-image-list-add .ajax-image-drop-text { width: 100%; min-width: 0; padding: 0 2px; }
.ajax-image-list-add .ajax-image-drop-title {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.2;
}
.ajax-image-list-add .ajax-image-drop-sub {
  display: block;
  font-size: 9px;
  color: #6b7280;
  line-height: 1.25;
  word-break: break-word;
}
.ajax-image-list-add .ajax-image-progress-wrap {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 4px 5px 5px;
  background: linear-gradient(to top, rgba(255,255,255,0.98) 50%, rgba(255,255,255,0));
}
.ajax-image-list-add .ajax-image-progress-track {
  height: 3px;
  border-radius: 999px;
  background: #e5e7eb;
  overflow: hidden;
}
.ajax-image-list-add .ajax-image-progress-bar {
  height: 100%;
  width: 0%;
  border-radius: 999px;
  background: linear-gradient(90deg, #3c8dbc, #5cb3d9);
}
.ajax-image-list-add .ajax-image-progress-label {
  display: block;
  margin-top: 2px;
  font-size: 8px;
  font-weight: 600;
  color: #4b5563;
  text-align: center;
}
CSS
        );
    }

    protected function registerAssets(): void
    {
        self::registerSharedListUiAssets();
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $this->addRequiredAttributeFromRules();
        $this->registerAssets();

        $this->setErrorKey((string) array_values($this->column)[0]);

        $disk = config('admin.upload.disk');
        $vals = (array) ($this->value() ?? []);
        $slots = [];
        foreach ($this->column as $key => $col) {
            $path = old($col, $vals[$key] ?? '');
            $path = is_string($path) ? trim($path) : '';
            $url = $path !== '' ? AdminUploadStorageUrl::url($path, $disk) : '';
            $slots[] = [
                'key' => $key,
                'name' => $col,
                'path' => $path,
                'url' => $url,
                'inputId' => $this->id.'_h_'.$key,
            ];
        }

        $max = count($slots);
        $initPayload = [];
        foreach ($slots as $s) {
            $initPayload[] = ['path' => $s['path'], 'url' => $s['url']];
        }

        $uploadUrl = admin_url('api/image-upload');
        $deleteUrl = admin_url('api/image-delete');
        $wrapId = $this->id.'_wrap';
        $fileInputId = $this->id.'_file';

        $uploadUrlJson = json_encode($uploadUrl);
        $deleteUrlJson = json_encode($deleteUrl);
        $wrapIdJson = json_encode($wrapId);
        $fileInputIdJson = json_encode($fileInputId);
        $initJson = json_encode($initPayload);
        $maxJson = json_encode($max);
        $msgBadTypeJson = json_encode(__('Please choose an image file.'));
        $msgMaxJson = json_encode(__('Maximum number of images reached.'));
        $msgOneOkJson = json_encode(__('Image uploaded'));
        $msgManySuffixJson = json_encode(__('images uploaded'));

        Admin::script(<<<JS
(function () {
  var uploadUrl = {$uploadUrlJson};
  var deleteUrl = {$deleteUrlJson};
  var wrapId = {$wrapIdJson};
  var fileInputId = {$fileInputIdJson};
  var initSlots = {$initJson};
  var maxN = {$maxJson};
  var msgBadType = {$msgBadTypeJson};
  var msgMax = {$msgMaxJson};
  var msgOneOk = {$msgOneOkJson};
  var msgManySuffix = {$msgManySuffixJson};
  var \$wrap = \$('#' + wrapId);
  if (!\$wrap.length) { return; }
  var \$items = \$wrap.find('.ajax-image-list-items');
  var \$add = \$wrap.find('.ajax-image-list-add');
  var \$zone = \$wrap.find('.ajax-image-list-add .ajax-image-filament-zone');
  var \$file = \$('#' + fileInputId);
  var \$hiddens = \$wrap.find('.ajax-image-list-hidden');
  var \$progWrap = \$wrap.find('.ajax-image-list-add .ajax-image-progress-wrap');
  var \$progBar = \$wrap.find('.ajax-image-list-add .ajax-image-progress-bar');
  var \$progLabel = \$wrap.find('.ajax-image-list-add .ajax-image-progress-label');
  var urlByPath = {};
  var dragCounter = 0;

  function eachHidden(cb) {
    \$hiddens.each(function (idx) { cb(\$(this), idx); });
  }

  function countFilled() {
    var n = 0;
    eachHidden(function (\$h) {
      if ((\$h.val() || '').trim()) { n++; }
    });
    return n;
  }

  function firstEmptyHidden() {
    var found = null;
    eachHidden(function (\$h) {
      if (found) { return; }
      if (!(\$h.val() || '').trim()) { found = \$h; }
    });
    return found;
  }

  function pathsCompact() {
    var p = [];
    eachHidden(function (\$h) {
      var v = (\$h.val() || '').trim();
      if (v) { p.push(v); }
    });
    return p;
  }

  function writeCompact(paths) {
    eachHidden(function (\$h, i) {
      var next = paths[i] || '';
      \$h.val(next);
    });
  }

  function thumbUrl(path) {
    if (!path) { return ''; }
    if (urlByPath[path]) { return urlByPath[path]; }
    return '';
  }

  function renderList() {
    \$items.empty();
    eachHidden(function (\$h) {
      var path = (\$h.val() || '').trim();
      if (!path) { return; }
      var u = thumbUrl(path);
      var base = path.split('/').pop() || path || 'image';
      var \$item = \$('<div class="ajax-image-list-item"/>').data('path', path);
      if (u) {
        \$item.addClass('ajax-image-list-item--has-img');
        \$item.append(\$('<img alt=""/>').attr('src', u));
        var \$dl = \$('<a class="ajax-image-list-dl" title="Download"/>')
          .attr('href', u).attr('target', '_blank').attr('download', base);
        \$dl.append(\$('<i class="fa fa-download"/>'));
        \$item.append(\$dl);
      } else {
        \$item.append(\$('<div class="ajax-image-list-ph"/>').css({padding:'8px',fontSize:'10px',color:'#888'}).text(path.split('/').pop() || path));
      }
      var \$rm = \$('<button type="button" class="ajax-image-list-remove" title="Remove"><i class="fa fa-times"/></button>');
      \$item.append(\$rm);
      \$items.append(\$item);
    });
    \$items.find('.ajax-image-list-item').each(function () {
      var \$it = \$(this);
      var p = \$it.data('path');
      var src = \$it.find('img').attr('src');
      if (p && src) { urlByPath[p] = src; }
    });
    if (countFilled() >= maxN) {
      \$add.addClass('hide');
    } else {
      \$add.removeClass('hide');
    }
  }

  function setProgress(pct, visible) {
    pct = Math.max(0, Math.min(100, pct));
    \$progBar.css('width', pct + '%');
    \$progLabel.text(visible ? (pct + '%') : '0%');
    \$progWrap.toggleClass('hide', !visible);
  }

  function setBusy(on) {
    \$zone.toggleClass('is-busy', !!on);
  }

  function filterImageFiles(fileList) {
    var imgs = [];
    var n = fileList ? fileList.length : 0;
    for (var i = 0; i < n; i++) {
      var f = fileList[i];
      if (f && f.type && f.type.indexOf('image/') === 0) {
        imgs.push(f);
      }
    }
    return imgs;
  }

  function uploadOneFile(file, onDone) {
    var done = function (ok) {
      if (typeof onDone === 'function') { onDone(!!ok); }
    };
    if (!file || !file.type || file.type.indexOf('image/') !== 0) {
      if (typeof toastr !== 'undefined') { toastr.error(msgBadType); }
      done(false);
      return;
    }
    if (countFilled() >= maxN) {
      if (typeof toastr !== 'undefined') { toastr.warning(msgMax); }
      done(false);
      return;
    }
    var \$target = firstEmptyHidden();
    if (!\$target) {
      if (typeof toastr !== 'undefined') { toastr.warning(msgMax); }
      done(false);
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
      },
      success: function (res) {
        if (res && res.ok && res.path) {
          \$target.val(res.path);
          if (res.url) { urlByPath[res.path] = res.url; }
          renderList();
          done(true);
        } else {
          if (typeof toastr !== 'undefined') {
            toastr.error((res && res.message) ? res.message : 'Upload failed');
          }
          done(false);
        }
      },
      error: function (xhr) {
        var msg = 'Upload failed';
        try {
          var j = xhr.responseJSON;
          if (j && j.message) { msg = j.message; }
        } catch (e) {}
        if (typeof toastr !== 'undefined') { toastr.error(msg); }
        done(false);
      }
    });
  }

  function uploadManyFiles(fileList) {
    var imgs = filterImageFiles(fileList);
    var rawN = fileList ? fileList.length : 0;
    if (rawN > 0 && imgs.length === 0) {
      if (typeof toastr !== 'undefined') { toastr.error(msgBadType); }
      \$file.val('');
      return;
    }
    if (imgs.length === 0) {
      \$file.val('');
      return;
    }
    var i = 0;
    var okCount = 0;
    function next() {
      if (countFilled() >= maxN) {
        \$file.val('');
        if (okCount > 0 && typeof toastr !== 'undefined') {
          if (okCount === 1) {
            toastr.success(msgOneOk);
          } else {
            toastr.success(okCount + ' ' + msgManySuffix);
          }
        }
        if (i < imgs.length && typeof toastr !== 'undefined') {
          toastr.warning(msgMax);
        }
        return;
      }
      if (i >= imgs.length) {
        \$file.val('');
        if (okCount > 0 && typeof toastr !== 'undefined') {
          if (okCount === 1) {
            toastr.success(msgOneOk);
          } else {
            toastr.success(okCount + ' ' + msgManySuffix);
          }
        }
        return;
      }
      var f = imgs[i++];
      uploadOneFile(f, function (ok) {
        if (ok) { okCount++; }
        next();
      });
    }
    next();
  }

  initSlots.forEach(function (s, i) {
    if (s.path && s.url) { urlByPath[s.path] = s.url; }
  });

  \$zone.on('click', function (e) {
    if (\$(e.target).closest('.ajax-image-list-remove').length) { return; }
    if (\$zone.hasClass('is-busy')) { return; }
    if (countFilled() >= maxN) { return; }
    e.preventDefault();
    var el = \$file[0];
    if (!el) { return; }
    window.setTimeout(function () { el.click(); }, 0);
  });

  \$zone.on('keydown', function (e) {
    if (e.keyCode === 13 || e.keyCode === 32) {
      e.preventDefault();
      if (\$zone.hasClass('is-busy') || countFilled() >= maxN) { return; }
      var el = \$file[0];
      if (el) { window.setTimeout(function () { el.click(); }, 0); }
    }
  });

  \$file.on('change', function () {
    if (!this.files || !this.files.length) { return; }
    uploadManyFiles(this.files);
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
    if (dragCounter <= 0) { dragCounter = 0; \$zone.removeClass('is-dragover'); }
  });
  \$zone.on('dragover', function (e) { e.preventDefault(); e.stopPropagation(); });
  \$zone.on('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter = 0;
    \$zone.removeClass('is-dragover');
    if (countFilled() >= maxN) { return; }
    var dt = e.originalEvent && e.originalEvent.dataTransfer;
    if (!dt || !dt.files || !dt.files.length) { return; }
    uploadManyFiles(dt.files);
  });

  \$items.on('click', '.ajax-image-list-dl', function (e) {
    e.stopPropagation();
  });

  \$items.on('click', '.ajax-image-list-item--has-img', function (e) {
    if (\$(e.target).closest('.ajax-image-list-remove, .ajax-image-list-dl').length) { return; }
    var \$img = \$(this).find('img').first();
    if (!\$img.length) { return; }
    var src = \$img.attr('src');
    if (!src) { return; }
    var path = \$(this).data('path') || '';
    var fn = (typeof path === 'string' && path.split) ? (path.split('/').pop() || 'image') : 'image';
    var \$lb = \$('#arex-ajax-img-lightbox');
    if (!\$lb.length) { return; }
    \$lb.find('#arex-ajax-img-lightbox-img').attr('src', src).attr('alt', fn);
    \$lb.find('#arex-ajax-img-lightbox-dl').attr('href', src).attr('download', fn);
    \$lb.find('.arex-ajax-img-lightbox-title').text(fn);
    \$lb.modal('show');
  });

  \$items.on('click', '.ajax-image-list-remove', function (e) {
    e.preventDefault();
    e.stopPropagation();
    var \$item = \$(this).closest('.ajax-image-list-item');
    var path = (\$item.data('path') || '').toString().trim();
    if (!path) { return; }
    setBusy(true);
    \$.ajax({
      url: deleteUrl,
      type: 'POST',
      data: {_token: LA.token, path: path},
      complete: function () { setBusy(false); },
      success: function (res) {
        if (res && res.ok) {
          eachHidden(function (\$h) {
            if ((\$h.val() || '').trim() === path) { \$h.val(''); }
          });
          var rest = pathsCompact();
          writeCompact(rest);
          delete urlByPath[path];
          renderList();
          if (typeof toastr !== 'undefined') { toastr.success('Image removed'); }
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
          if (j && j.message) { msg = j.message; }
        } catch (err) {}
        if (typeof toastr !== 'undefined') { toastr.error(msg); }
      }
    });
  });

  renderList();
})();
JS
        );

        return view('admin::form.ajax_image_list', array_merge($this->variables(), [
            'slots' => $slots,
            'wrapId' => $wrapId,
            'fileInputId' => $fileInputId,
            'max' => $max,
        ]))->render();
    }
}
