<?php
namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;
use Encore\Admin\Extensions\Tools\ModalField;

class Value2 extends Field
{
	protected $format = '';
	protected $modal = '';
	public $options = [];
	public $displayCallbacks = array();
	
	public function dataFormat($format){
		$this->format = $format;
		return $this;
	}
	
	public function options($options=[]){
		$this->options = $options;
		return $this;
	}
	
	public function writeable($d = 'text',$option=[]){
		$modal = new ModalField();
		$modal->setForm($this->form);
		if(is_array($d)){
			$columns = $d;
		}
		else{
		    $columns = [[$this->column,$this->label,$d,$option]];
		}
		$modal->setColumns($columns);
		$this->modal =  $modal->render();
		return $this;
	}
	
	public function display(\Closure $callback)
    {
        $this->displayCallbacks[] = $callback;

        return $this;
    }
	
	protected function hasDisplayCallbacks()
    {
        return !empty($this->displayCallbacks);
    }

    protected function callDisplayCallbacks($value, $key)
    {
        foreach ($this->displayCallbacks as $callback) {
            $previous = $value;

            $callback = $this->bindOriginalModel($callback, $key);
            $value = call_user_func_array($callback, [$value, $this]);

            if (($value instanceof static) &&
                ($last = array_pop($this->displayCallbacks))
            ) {
                $last = $this->bindOriginalModel($last, $key);
                $value = call_user_func($last, $previous);
            }
        }

        return $value;
    }
	
	protected function bindOriginalModel(\Closure $callback, $key)
    {
        return $callback->bindTo($this->form->model());
    }
	
    public function render()
    {
		$value = old($this->elementName ?: $this->column, $this->value());
		
		if ($this->hasDisplayCallbacks()) {
            $value = $this->callDisplayCallbacks($value, $this->column);
        }
			
		if($this->format == 'Date'){
			$value = date('Y-m-d',strtotime($value));
		}
		elseif($this->format == 'InvoiceNumber'){
			$value = sprintf("%06d", $value);
		}
		elseif($this->format!=''){
			$value = $this->format.number_format($value,2);
		}
		$viewClass = $this->getViewElementClasses();
		
		//if($value == "")return "";
		if(count($this->options)>0 && is_array($value)){
			$temp = '';
			foreach($value as $subvalue){
				$temp .= "<span class='label label-success'>".$this->options[(int)$subvalue]."</span>&nbsp;";
			}
			$value = $temp;
		}
		else if(count($this->options)>0 && isset($this->options[$value]))$value = $this->options[$value];
        return <<<EOT
<div class="{$viewClass['form-group']}">
    <label  class="{$viewClass['label']} control-label">{$this->label}</label>
    <div class="{$viewClass['field']}" style="padding-top:7px;word-wrap:break-word;">
	  {$value} {$this->modal}
    </div>
</div>
EOT;
    }
}
