<?php

namespace Encore\Admin\Forms\Field;

use Encore\Admin\Admin;
use Encore\Admin\Form\Field\Select;
use Encore\Admin\Form\Field\BelongsToRelation;

class Rma extends Select
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
		$(document).ready(function(){
			$('.QTY_ISSUE').unbind('change').change(function(e) {
				if($(this).val() <=0)$(this).val(1);
				if(itemdata!=null)
				if($(this).val() > itemdata.saleqty)$(this).val(itemdata.saleqty);
			});
			
			$('.UNITPRICE').unbind('change').change(function(e) {
				if($(this).val() <0)$(this).val(0);
				if($(this).val() > $('.UNITPRICE_WITHOUT_DISCOUNT').val())$(this).val($('.UNITPRICE_WITHOUT_DISCOUNT').val());
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

	
	$('.UNITNUMBER').unbind('change').change(function(){
		if(itemdata!=null){
			
			if(itemdata.unitnumber == 0){
			   var v = $(this).val();
			   if(v == 0){
				    $('.UNITPRICE').val(itemdata.returnprice);
					$('.UNITPRICE_WITHOUT_DISCOUNT').val(itemdata.returnpricewithoutdiscount);
					$('.SALETAX').val(itemdata.saletax);
					$('.DISCOUNT').val(itemdata.discount);
			   }
			   else{
				   $('.UNITPRICE').val(itemdata.returnprice/2);
				   $('.UNITPRICE_WITHOUT_DISCOUNT').val(itemdata.returnpricewithoutdiscount/2);
				   $('.SALETAX').val(itemdata.saletax/2);
				   $('.DISCOUNT').val(itemdata.discount/2);
			   }
			}
		}
	});
	
	var loaddetail = function (url) {
        $.get(url, function (data) {
			itemdata = JSON.parse(data);
			$('.UNITPRICE').val(itemdata.returnprice);
			$('.UNITPRICE_WITHOUT_DISCOUNT').val(itemdata.returnpricewithoutdiscount);
			$('.SALETAX').val(itemdata.saletax);
			$('.DISCOUNT').val(itemdata.discount);
			$('.QTY_IUSSE').val(1);
            updateunit(itemdata.unitnumber);
        });
    };
	
	var updateunit = function(v){
		$(".UNITNUMBER").empty();
		if(v == 1){
			$(".UNITNUMBER").select2({data: [{ id: 1, text: 'Left' }],allowClear:false}).val(v).trigger('change');
		}
		else if(v == 2){
			$(".UNITNUMBER").select2({data: [{ id: 2, text: 'Right' }],allowClear:false}).val(v).trigger('change');
		}
		else{
			$(".UNITNUMBER").select2({data: [{ id: 0, text: 'Set' },{ id: 1, text: 'Left' },{ id: 2, text: 'Right' }],allowClear:false}).val(0).trigger('change');
		}
	}

    var update = function (callback) {
        $("{$this->getElementClassSelector()}")
            .select2({data: [selected]})
            .val(selected)
            .trigger('change')
            .next()
            .addClass('hide');
			
        loaddetail("{$this->getLoadDetailUrl()}&i="+selected); //load detail by widia
		
        if (row) {
            row.find('td:last a').removeClass('hide');
            row.find('td:first').remove();
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
