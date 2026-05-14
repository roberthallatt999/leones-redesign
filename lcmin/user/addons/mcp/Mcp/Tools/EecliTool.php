<?php

namespace ExpressionEngine\Addons\Mcp\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;
use Mcp\Capability\Attribute\McpTool;

/**
 * EECLI Tool
 *
 * Provides functionality to list and execute ExpressionEngine CLI commands.
 * This tool allows loading available commands and running them with arguments and options.
 */
#[EeCategory('developer')]
#[McpTool(
    name: 'eecli',
    description: 'List and execute ExpressionEngine CLI commands'
)]
class EecliTool extends AbstractTool
{
    /**
     * Path to eecli.php script
     */
    private function getEecliPath(): string
    {
        return SYSPATH.'ee/eecli.php';
    }

    public function description(): string
    {
        return 'List and execute ExpressionEngine CLI commands. Use action "list" to see available commands, or "run" to execute a command.';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return $schema->object([
            'action' => $schema->enum(['list', 'run'])
                ->description('Action to perform: "list" to get available commands, "run" to execute a command')
                ->required(),
            'command' => $schema->string()
                ->description('The CLI command to execute (e.g., "cache:clear", "list", "version"). Required when action is "run".'),
            'arguments' => $schema->array($schema->string()->toArray())
                ->description('Array of command arguments to pass to the command'),
            'options' => $schema->object()
                ->description('Object with option flags (e.g., {"verbose": true, "force": true, "step": "2"}). Boolean options are set as flags, string/number options are passed as --key=value. Any option key is allowed.'),
            'format' => $schema->enum(['text', 'json'])
                ->description('Output format. "json" attempts to parse JSON output from commands that support it (e.g., --json flag). Default: "text"')
                ->default('text'),
        ], ['action'])->toArray();
    }

    public function isDestructive(): bool
    {
        return true;
    }

    public function isOpenWorld(): bool
    {
        return true;
    }

    public function handle(array $params): array
    {
        $action = $params['action'] ?? null;
        $format = $params['format'] ?? 'text';

        if (! $action) {
            throw new \InvalidArgumentException('Action is required. Use "list" to see available commands or "run" to execute a command.');
        }

        if ($action === 'list') {
            return $this->listCommands();
        }

        if ($action === 'run') {
            $command = $params['command'] ?? null;
            if (! $command) {
                throw new \InvalidArgumentException('Command is required when action is "run".');
            }

            $arguments = $params['arguments'] ?? [];
            $options = $params['options'] ?? [];

            return $this->runCommand($command, $arguments, $options, $format);
        }

        throw new \InvalidArgumentException("Invalid action: {$action}. Use 'list' or 'run'.");
    }

    /**
     * List all available CLI commands
     */
    private function listCommands(): array
    {
        // Use shell execution for reliability
        // This avoids issues with protected methods and EE bootstrap state
        return $this->listCommandsViaShell();
    }

    /**
     * List commands via shell execution
     */
    private function listCommandsViaShell(): array
    {
        $eecliPath = $this->getEecliPath();

        if (! file_exists($eecliPath)) {
            throw new \RuntimeException("EECLI script not found at: {$eecliPath}");
        }

        // Execute list command with --simple flag for easier parsing
        $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($eecliPath).' list --simple 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        $output = implode("\n", $output);

        if ($exitCode !== 0 && empty($output)) {
            throw new \RuntimeException("Failed to list commands. Exit code: {$exitCode}");
        }

        $commandLines = array_filter(array_map('trim', explode("\n", trim($output))));
        $commands = [];

        foreach ($commandLines as $line) {
            if (! empty($line)) {
                $commands[] = [
                    'signature' => $line,
                    'name' => $line,
                    'description' => '',
                    'usage' => '',
                ];
            }
        }

        return [
            'success' => true,
            'action' => 'list',
            'commands' => $commands,
            'count' => count($commands),
            'timestamp' => date('c'),
        ];
    }

    /**
     * Execute a CLI command
     */
    private function runCommand(string $command, array $arguments = [], array $options = [], string $format = 'text'): array
    {
        $eecliPath = $this->getEecliPath();

        if (! file_exists($eecliPath)) {
            throw new \RuntimeException("EECLI script not found at: {$eecliPath}");
        }

        // Build the command string
        $cmdParts = [escapeshellarg(PHP_BINARY), escapeshellarg($eecliPath), escapeshellarg($command)];

        // Add arguments
        foreach ($arguments as $arg) {
            $cmdParts[] = escapeshellarg($arg);
        }

        // Add options
        foreach ($options as $key => $value) {
            // Validate option key (alphanumeric, dash, underscore only)
            if (! preg_match('/^[a-zA-Z0-9_-]+$/', $key)) {
                throw new \InvalidArgumentException("Invalid option key: {$key}. Only alphanumeric characters, dashes, and underscores are allowed.");
            }

            if (is_bool($value)) {
                if ($value) {
                    $cmdParts[] = '--'.$key;
                }
            } elseif (is_string($value) || is_numeric($value)) {
                $cmdParts[] = '--'.$key.'='.escapeshellarg((string) $value);
            } elseif (is_array($value)) {
                // Serialize arrays as JSON strings
                $jsonValue = json_encode($value);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cmdParts[] = '--'.$key.'='.escapeshellarg($jsonValue);
                } else {
                    throw new \InvalidArgumentException("Invalid option value for '{$key}': array could not be serialized to JSON.");
                }
            } elseif (is_object($value)) {
                // Convert objects to arrays and serialize as JSON
                $arrayValue = (array) $value;
                $jsonValue = json_encode($arrayValue);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cmdParts[] = '--'.$key.'='.escapeshellarg($jsonValue);
                } else {
                    throw new \InvalidArgumentException("Invalid option value for '{$key}': object could not be serialized to JSON.");
                }
            } else {
                throw new \InvalidArgumentException("Invalid option value type for '{$key}': only boolean, string, number, array, or object are supported.");
            }
        }

        // If format is json, try to add --json flag if command supports it
        // We'll handle the case where --json is not supported by retrying without it
        $tryJsonFlag = ($format === 'json' && ! isset($options['json']));
        $hasExplicitJson = isset($options['json']) && $options['json'] === true;

        if ($tryJsonFlag) {
            $cmdParts[] = '--json';
        }

        $fullCommand = implode(' ', $cmdParts).' 2>&1';

        // Execute the command and capture exit code
        $output = [];
        $exitCode = 0;
        exec($fullCommand, $output, $exitCode);
        $output = implode("\n", $output);

        // If --json flag was used (either auto-added or explicit) and command failed with "not defined" error, retry without it
        if (($tryJsonFlag || $hasExplicitJson) && $exitCode !== 0 && stripos($output, "The option '--json' is not defined") !== false) {
            // Remove --json flag and retry
            $cmdPartsWithoutJson = array_filter($cmdParts, function ($part) {
                return $part !== '--json';
            });
            $fullCommand = implode(' ', $cmdPartsWithoutJson).' 2>&1';
            $output = [];
            $exitCode = 0;
            exec($fullCommand, $output, $exitCode);
            $output = implode("\n", $output);
        }

        // Try to parse JSON output if format is json
        $parsedOutput = null;
        if ($format === 'json' && ! empty($output)) {
            $trimmedOutput = trim($output);

            // First try parsing the entire output as JSON
            $json = json_decode($trimmedOutput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $parsedOutput = $json;
            } else {
                // If that fails, try to find JSON objects in the output
                // Use a more precise method: find balanced braces
                $parsedOutput = $this->extractJsonFromOutput($trimmedOutput);
            }
        }

        $result = [
            'success' => $exitCode === 0,
            'action' => 'run',
            'command' => $command,
            'exit_code' => $exitCode,
            'output' => $output,
            'timestamp' => date('c'),
        ];

        if ($parsedOutput !== null) {
            $result['parsed_output'] = $parsedOutput;
        }

        if ($exitCode !== 0) {
            $result['error'] = "Command failed with exit code {$exitCode}";
        }

        return $result;
    }

    /**
     * Extract JSON from output text using balanced brace matching
     * This is more reliable than regex for nested JSON objects
     */
    private function extractJsonFromOutput(string $output): ?array
    {
        $startPos = strpos($output, '{');
        if ($startPos === false) {
            return null;
        }

        // Find the matching closing brace by counting braces
        $braceCount = 0;
        $endPos = $startPos;
        $length = strlen($output);

        for ($i = $startPos; $i < $length; $i++) {
            if ($output[$i] === '{') {
                $braceCount++;
            } elseif ($output[$i] === '}') {
                $braceCount--;
                if ($braceCount === 0) {
                    $endPos = $i;
                    break;
                }
            }
        }

        if ($braceCount !== 0) {
            // Unbalanced braces, try parsing entire output again
            $json = json_decode($output, true);

            return (json_last_error() === JSON_ERROR_NONE) ? $json : null;
        }

        // Extract the JSON substring
        $jsonString = substr($output, $startPos, $endPos - $startPos + 1);
        $json = json_decode($jsonString, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return null;
    }
}
