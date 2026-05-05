<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_03 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'class' => 'Ansel_ext',
                'hook' => 'after_file_delete',
                'method' => 'after_file_delete',
            ],
        ]);
    }
}
