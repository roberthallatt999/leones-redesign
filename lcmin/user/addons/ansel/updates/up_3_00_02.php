<?php

use BoldMinded\Ansel\Dependency\Litzinger\Basee\Update\AbstractUpdate;

class Update_3_00_02 extends AbstractUpdate
{
    public function doUpdate()
    {
        $this->addHooks([
            [
                'class' => 'Ansel_ext',
                'hook' => 'after_channel_field_delete',
                'method' => 'after_channel_field_delete',
            ],
        ]);
    }
}
