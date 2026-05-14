<?php

namespace ExpressionEngine\Addons\Mcp\Commands;

use ExpressionEngine\Cli\Cli;

class CommandServeCommand extends Cli
{
    /**
     * name of command
     *
     * @var string
     */
    public $name = 'ServeCommand';

    /**
     * signature of command
     *
     * @var string
     */
    public $signature = 'mcp:serve';

    /**
     * Public description of command
     *
     * @var string
     */
    public $description = 'Start the Mcp server';

    /**
     * Summary of command functionality
     *
     * @var [type]
     */
    public $summary = 'Start the Mcp server';

    /**
     * How to use command
     *
     * @var string
     */
    public $usage = 'php eecli.php mcp:serve';

    /**
     * options available for use in command
     *
     * @var array
     */
    public $commandOptions = [
    ];

    /**
     * Run the command
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // Get the McpServer service
            $server = ee('mcp:McpServer');

            // Start the server
            $server->start();
        } catch (\Throwable $e) {
            $this->fail('[CRITICAL ERROR] '.$e->getMessage());
            exit(1);
        }
    }
}
