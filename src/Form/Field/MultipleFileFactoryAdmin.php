<?php

namespace Encore\FactoryAdmin\Forms\Field;

use Encore\Admin\Form\Field;
use Encore\Admin\Extensions\Tools\ModalField;
use Encore\Admin\Form\Field\MultipleFile;

class MultipleFileFactoryAdmin extends MultipleFile
{
	protected function initStorage()
    {
        $this->disk(config('factoryadmin.upload.disk'));
    }
}
