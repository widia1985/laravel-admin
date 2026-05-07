<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;

class Value extends Field
{
	protected $format = '';
	public $options = [];
	
	public function dataFormat($format){
		$this->format = $format;
		return $this;
	}
	
	public function options($options=[]){
		$this->options = $options;
		return $this;
	}
	
    public function render()
    {
		$value = old($this->elementName ?: $this->column, $this->value());
		if($this->format == 'Date'){
			$value = date('Y-m-d',strtotime($value));
		}
		elseif($this->format == 'currency'){
			$value = "$".number_format($value,2);
		}
		elseif($this->format == 'InvoiceNumber'){
			$value = sprintf("%06d", $value);
		}
		$viewClass = $this->getViewElementClasses();
		
		if($value == "")return "";
		if(count($this->options)>0 && isset($this->options[$value]))$value = $this->options[$value];
        return <<<EOT
<div class="{$viewClass['form-group']}">
    <label  class="{$viewClass['label']} control-label">{$this->label}</label>
    <div class="{$viewClass['field']}" style="padding-top:7px;word-wrap:break-word;">
	  {$value}
    </div>
</div>
EOT;
    }
}
