<?php
namespace Encore\Admin\Extensions\Tools;

use Illuminate\Http\Request;
use Encore\Admin\Actions\Action;
use Encore\Admin\Form;

class ModalField extends Action
{
	public $name = 'Update';
	public $class = "";
	public $sponser = null;
	protected $form = null;
	protected $columns = null;
	
	public function render()
    {
        $this->addScript();

        $modalId = '';

        if ($this->interactor instanceof \Encore\Admin\Actions\Interactor\Form) {
			
            $modalId = $this->interactor->getModalId();

            if ($content = $this->html()) {
                return $this->interactor->addElementAttr($content, $this->selector);
            }
        }

        return sprintf(
            "<a class='btn btn-sm %s' data-toggle='modal' %s><i class='fa fa-pencil fa-fw'></i></a>",
            $this->getElementClass(),
            $modalId ? "modal='{$modalId}'" : '',
            $this->name
        );
    }
	
	protected function getElementClass()
    {
        return $this->class." ".parent::getElementClass();
    }
	

	
	public function setForm($form)
    {
        $this->form = $form;

        return $this;
    }
	
	public function setColumns($columns)
    {
        $this->columns = $columns;

        return $this;
    }
	
	public function form()
    {
		if($this->form!=null){
			$model = $this->form->model();
			$this->hidden('class')->value('\\'.get_class($model));
			$this->hidden('id')->value($model->getKey());
			
			foreach($this->columns as $column){
				$field = $this->{$column[2]}("update_".$column[0], $column[1]);
				if(count($column[3])>0){
					$field->options($column[3]);
				}
				$field->value($model->{$column[0]});
			}
		}
    }
	
    public function handle(Request $request)
    {
		$class = $request->get('class');
		$id = $request->get('id');
		$model = $class::find($id);
		$data = $request->all();
		foreach($data as $k=>$v){
			if(substr($k,0,6) == 'update'){
				$column = substr($k,7,strlen($k)-7);
				$model->{$column} = $v;
			}
		}
		$model->save();
        return $this->response()->success('Success...')->refresh();
    }
}
?>
