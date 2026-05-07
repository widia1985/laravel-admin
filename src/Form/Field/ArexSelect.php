<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field\Select;

class ArexSelect extends Select
{
	public function ajax($url, $idField = 'id', $textField = 'text', $filterFields = [])
    {
        $configs = array_merge([
            'allowClear'         => true,
            'placeholder'        => $this->label,
            'minimumInputLength' => 1,
        ], $this->config);

        $configs = json_encode($configs);
        $configs = substr($configs, 1, strlen($configs) - 2);
        $strFilter = json_encode($filterFields);
        $this->script = <<<EOT

$("{$this->getElementClassSelector()}").select2({
  ajax: {
    url: "$url",
    dataType: 'json',
    delay: 250,
    data: function (params) {
        var filterFields = $strFilter;
        var extraParams = [];
        filterFields.forEach(function (field) {
            var el = $('[name="' + field + '"]');

            if (el.length) {
                if (el.attr('type') === 'checkbox') {
                    // ✅ checkbox 只取 checked 的值
                    extraParams[field.replace('[]', '')] = el.filter(':checked').map(function () {
                        return $(this).val();
                    }).get().join(',');
                } else if (field.endsWith('[]')) {
                    // ✅ 多选框或 select[multiple] 其他类型的数组字段
                    extraParams[field.replace('[]', '')] = el.map(function () {
                        return $(this).val();
                    }).get().join(',');
                } else {
                    // ✅ 普通字段
                    extraParams[field] = el.val();
                }
            }
        });

      return Object.assign({
        q: params.term,
        page: params.page
      }, extraParams);
    },
    processResults: function (data, params) {
      params.page = params.page || 1;

      return {
        results: $.map(data.data, function (d) {
                   d.id = d.$idField;
                   d.text = d.$textField;
                   return d;
                }),
        pagination: {
          more: data.next_page_url
        }
      };
    },
    cache: true
  },
  $configs,
  escapeMarkup: function (markup) {
      return markup;
  }
});

EOT;

        return $this;
    }
}
