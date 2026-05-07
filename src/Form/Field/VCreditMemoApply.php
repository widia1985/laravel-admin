<?php

namespace Encore\Admin\Forms\Field;

use Encore\Admin\Admin;
use Encore\Admin\Form\Field\Select;
use Encore\Admin\Form\Field\BelongsToRelation;

class VCreditMemoApply extends Select
{
    use BelongsToRelation;
	public $filter = [];

    public function setFilter($vs = []){
		$this->filter = $vs;
		return $this;
	}
	
	 protected function getLoadUrl($multiple = 0)
    {
        $selectable = str_replace('\\', '_', $this->selectable);
		$custid = 0;
		if(isset($this->filter['CUSTID']))$custid=$this->filter['CUSTID'];

        return route('admin.handle-selectable', compact('selectable', 'multiple','custid'));
    }
	
	protected function getLoadDetailUrl()
    {
		$custid = 0;
		if(isset($this->filter['CUSTID']))$custid=$this->filter['CUSTID'];
        return '/admin/api/detailforrma?c='.$custid;
    }

	
    protected function addScript()
    {
        $script = <<<SCRIPT
		var itemdata = null;
		var left = 0;
		var unpaidamt = $('.unpaidamt').val();
		$(document).ready(function(){
			$('.cmamt').unbind('change').change(function(e) {
				if($(this).val() <=0 || $(this).val()>left || $(this).val()>unpaidamt){
					if(left>unpaidamt)$(this).val(unpaidamt);
					else $(this).val(left);
				}
			});
		});
(function () {

    var grid = $('.belongsto-{$this->column()}');
	$('.belongsto-{$this->column()}').find('.empty-grid').css('padding',0);
    var modal = $('#{$this->modalID}');
    var table = grid.find('.grid-table');
    var selected = $("{$this->getElementClassSelector()}").val();
    var row = null;
	

    // open modal
    grid.find('.select-relation').click(function (e) {
        $('#{$this->modalID}').modal('show');
        e.preventDefault();
    });

    // remove row
    grid.on('click', '.grid-row-remove', function () {
        selected = null;
        $(this).parents('tr').remove();
        $("{$this->getElementClassSelector()}").val(null);
        
        var empty = $('.belongsto-{$this->column()}').find('template.empty').html();

        table.find('tbody').append(empty);
		$('.belongsto-{$this->column()}').find('.empty-grid').css('padding',0);
    });

    var load = function (url) {
        $.get(url, function (data) {
            modal.find('.modal-body').html(data);
            modal.find('.select').iCheck({
                radioClass:'iradio_minimal-blue',
                checkboxClass:'icheckbox_minimal-blue'
            });
            modal.find('.box-header:first').hide();

            modal.find('input.select').each(function (index, el) {
                if ($(el).val() == selected) {
                    $(el).iCheck('toggle');
                }
            });
        });
    };

    var update = function (callback) {
        $("{$this->getElementClassSelector()}")
            .select2({data: [selected]})
            .val(selected)
            .trigger('change')
            .next()
            .addClass('hide');
			
        if (row) {
            row.find('td:last a').removeClass('hide');
            row.find('td:first').remove();
			left = parseFloat(row.find('.column-creditleft').text().replace('$','').replace(',',''));
			if(left>unpaidamt){
				$('.cmamt').val(unpaidamt);
			}
			else{
				$('.cmamt').val(left);
			}
            table.find('tbody').empty().append(row);
        }

        callback();
    };

    modal.on('show.bs.modal', function (e) {
        load("{$this->getLoadUrl()}");
    }).on('click', '.page-item a, .filter-box a', function (e) {
        load($(this).attr('href'));
        e.preventDefault();
    }).on('click', 'tr', function (e) {
        $(this).find('input.select').iCheck('toggle');
        e.preventDefault();
    }).on('submit', '.box-header form', function (e) {
        load($(this).attr('action')+'&'+$(this).serialize());
        return false;
    }).on('ifChecked', 'input.select', function (e) {
        row = $(e.target).parents('tr');
        selected = $(this).val();
    }).find('.modal-footer .submit').click(function () {
        update(function () {
            modal.modal('toggle');
        });
    });
})();
SCRIPT;

        Admin::script($script);

        return $this;
    }

    protected function getOptions()
    {
        $options = [];

        if ($value = $this->value()) {
            $options = [$value => $value];
        }

        return $options;
    }
}
