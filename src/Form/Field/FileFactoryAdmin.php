<?php

namespace Encore\Admin\Form\Field;

use Encore\Admin\Form\Field;
use Encore\Admin\Extensions\Tools\ModalField;
use Encore\Admin\Form\Field\File;

class FileFactoryAdmin extends File
{
	protected function initStorage()
    {
        $this->disk(config('factoryadmin.upload.disk'));
    }
}
