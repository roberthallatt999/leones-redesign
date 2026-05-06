<?php

declare (strict_types=1);
namespace BoldMinded\Ansel\Dependency\Bamarni\Composer\Bin\ApplicationFactory;

use BoldMinded\Ansel\Dependency\Composer\Console\Application;
final class FreshInstanceApplicationFactory implements NamespaceApplicationFactory
{
    public function create(Application $existingApplication) : Application
    {
        return new Application();
    }
}
