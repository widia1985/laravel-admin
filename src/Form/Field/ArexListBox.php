<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field\Listbox;

class ArexListBox extends Listbox
{
	public function fill($data)
    {
        $this->data = $data;
		$this->view ='admin.form.listbox';

        if (is_array($this->column)) {
            foreach ($this->column as $key => $column) {
                $this->value[$key] = \Illuminate\Support\Arr::get($data, $column);
            }

            return;
        }

        if(strpos($this->column,"_KEY")){
			$key = substr($this->column,0,strpos($this->column,"_KEY"));
		}
		else{
			$key = $this->column;
		}
	
        $this->value = \Illuminate\Support\Arr::get($data, $key);

        $this->formatValue();
    }
}
