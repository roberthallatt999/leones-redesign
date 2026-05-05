<?php

namespace BoldMinded\Ansel\Dependency\ImageOptimizer;

use BoldMinded\Ansel\Dependency\ImageOptimizer\Exception\Exception;
interface Optimizer
{
    /**
     * @param string $filepath Filepath to file to optimize, it will be overwrite if optimization succeed
     * @return void
     * @throws Exception
     */
    public function optimize($filepath);
}
