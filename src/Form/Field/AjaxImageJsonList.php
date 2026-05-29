<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Support\AdminUploadStorageUrl;
use Encore\Admin\Admin;
use Encore\Admin\Form\Field;

/**
 * Same UI as AjaxImageList; stores ordered paths in one model attribute (array / JSON via mutator).
 */
class AjaxImageJsonList extends Field
{
    protected $view = '';

    protected $rules = [];

    protected $maxSlots = 30;

    public function getValidator(array $input)
    {
        return false;
    }

    public function __construct($column, $arguments = [])
    {
        if (!is_string($column) || $column === '') {
            throw new \InvalidArgumentException('ajaxImageJsonList requires a string column name.');
        }

        $max = isset($arguments[1]) ? (int) $arguments[1] : 30;
        if ($max < 1) {
            $max = 1;
        }
        if ($max > 100) {
            $max = 100;
        }
        $this->maxSlots = $max;

        parent::__construct($column, $arguments);
        $this->id = 'ailj_'.substr(md5($column.'|'.$max), 0, 14);
    }

    /**
     * @return $this
     */
    public function maxSlots(int $max): self
    {
        $max = max(1, min(100, $max));
        $this->maxSlots = $max;

        return $this;
    }

    /**
     * @param  mixed  $value
     * @return array
     */
    public function prepare($value)
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach ($value as $p) {
            if (!is_string($p)) {
                continue;
            }
            $p = trim(str_replace('\\', '/', $p));
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return array_values($out);
    }

    protected function registerAssets(): void
    {
        AjaxImageList::registerSharedListUiAssets();
    }

    public function render()
    {
        if (!$this->shouldRender()) {
            return '';
        }

        $this->addRequiredAttributeFromRules();
        $this->registerAssets();

        $this->setErrorKey($this->column);

        $disk = config('admin.upload.disk');
        $fromModel = $this->value();
        if (!is_array($fromModel)) {
            $fromModel = [];
        }
        $fromModel = array_values(array_filter($fromModel, function ($p) {
            return is_string($p) && trim($p) !== '';
        }));

        $old = old($this->column);
        $paths = is_array($old)
            ? array_values(array_filter($old, function ($p) {
                return is_string($p) && trim($p) !== '';
            }))
            : $fromModel;

        $nameWithBrackets = $this->column.'[]';
        $slots = [];
        for ($i = 0; $i < $this->maxSlots; $i++) {
            $path = isset($paths[$i]) && is_string($paths[$i]) ? trim($paths[$i]) : '';
            $url = $path !== '' ? AdminUploadStorageUrl::url($path, $disk) : '';
            $slots[] = [
                'key' => $i,
                'name' => $nameWithBrackets,
                'path' => $path,
                'url' => $url,
                'download_url' => $path !== '' ? admin_url('api/image-download?path='.rawurlencode($path)) : '',
                'inputId' => $this->id.'_h_'.$i,
            ];
        }

        $initPayload = [];
        foreach ($slots as $s) {
            $initPayload[] = ['path' => $s['path'], 'url' => $s['url'], 'download_url' => $s['download_url']];
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
        $maxJson = json_encode($this->maxSlots);
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
  var downloadUrlByPath = {};
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

  function downloadUrl(path) {
    if (!path) { return ''; }
    if (downloadUrlByPath[path]) { return downloadUrlByPath[path]; }
    return '';
  }

  function renderList() {
    \$items.empty();
    eachHidden(function (\$h) {
      var path = (\$h.val() || '').trim();
      if (!path) { return; }
      var u = thumbUrl(path);
      var dlUrl = downloadUrl(path);
      var base = path.split('/').pop() || path || 'image';
      var \$item = \$('<div class="ajax-image-list-item"/>').data('path', path);
      if (u) {
        \$item.addClass('ajax-image-list-item--has-img');
        \$item.append(\$('<img alt=""/>').attr('src', u));
        var \$dl = \$('<a class="ajax-image-list-dl" title="Download"/>')
          .attr('href', dlUrl || u).attr('target', '_blank').attr('download', base);
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
          if (res.download_url) { downloadUrlByPath[res.path] = res.download_url; }
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

  initSlots.forEach(function (s) {
    if (s.path && s.url) { urlByPath[s.path] = s.url; }
    if (s.path && s.download_url) { downloadUrlByPath[s.path] = s.download_url; }
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
    var dlUrl = downloadUrl(path) || src;
    var \$lb = \$('#arex-ajax-img-lightbox');
    if (!\$lb.length) { return; }
    \$lb.find('#arex-ajax-img-lightbox-img').attr('src', src).attr('alt', fn);
    \$lb.find('#arex-ajax-img-lightbox-dl').attr('href', dlUrl).attr('download', fn);
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
          delete downloadUrlByPath[path];
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
            'max' => $this->maxSlots,
        ]))->render();
    }
}
