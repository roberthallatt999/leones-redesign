<?php

namespace ExpressionEngine\Addons\Mcp\Commands;

use ExpressionEngine\Cli\Cli;

class CommandInstallCommand extends Cli
{
    /**
     * name of command
     *
     * @var string
     */
    public $name = 'InstallCommand';

    /**
     * signature of command
     *
     * @var string
     */
    public $signature = 'mcp:install';

    /**
     * Public description of command
     *
     * @var string
     */
    public $description = 'Install the MCP server configuration for your IDE';

    /**
     * Summary of command functionality
     *
     * @var string
     */
    public $summary = 'Install the MCP server configuration for your IDE';

    /**
     * How to use command
     *
     * @var string
     */
    public $usage = 'php eecli.php mcp:install [--ide=cursor|claude|codex|auto] [--cursor|--claude|--codex] [--server-name=<name>|--name=<name>] [--project-root=<path>] [--yes]';

    /**
     * options available for use in command
     *
     * @var array
     */
    public $commandOptions = [
        'ide::' => 'Specify the IDE to configure (cursor, claude, codex, or auto-detect). Default: auto',
        'cursor' => 'Install MCP configuration for Cursor (alias for --ide=cursor)',
        'claude' => 'Install MCP configuration for Claude (alias for --ide=claude)',
        'codex' => 'Install MCP configuration for Codex (alias for --ide=codex)',
        'server-name::' => 'Set custom MCP server name (example: site1-ee-mcp). Alias: --name',
        'name::' => 'Alias for --server-name',
        'project-root::' => 'Project root where local IDE config files should be written (Cursor/Claude). Defaults to detected root from eecli.php',
        'yes' => 'Skip confirmation prompts (useful for non-interactive scripts)',
    ];

    /**
     * Run the command
     *
     * @return mixed
     */
    public function handle()
    {
        // Resolve IDE selection from flags/options or interactive prompt.
        $ide = $this->resolveInstallTargetIde();
        $normalizedIde = strtolower($ide);
        $serverName = $this->resolveServerNameOption($normalizedIde);
        if (! $this->isValidServerName($serverName)) {
            $this->fail("Invalid MCP server name: \"{$serverName}\". Use letters, numbers, dot, underscore, or hyphen.");

            return 1;
        }

        // Get the ExpressionEngine installation path
        $eeCliPath = $this->getEeCliPath();
        if (! $eeCliPath) {
            $this->fail('Could not locate ExpressionEngine CLI (eecli.php). Please ensure you are running this command from within an ExpressionEngine installation.');

            return 1;
        }

        // Normalize the path
        $eeCliPath = realpath($eeCliPath);
        if ($eeCliPath === false) {
            $this->fail('Could not resolve ExpressionEngine CLI path.');

            return 1;
        }

        $projectRoot = null;
        if ($normalizedIde !== 'codex') {
            $projectRoot = $this->resolveInstallProjectRoot($this->resolveProjectRoot($eeCliPath));
            if (! is_dir($projectRoot)) {
                $this->fail("Project root does not exist or is not a directory: {$projectRoot}");

                return 1;
            }
        }

        $this->info('Installing ExpressionEngine MCP server configuration...');
        $this->info("Detected ExpressionEngine CLI: {$eeCliPath}");
        if ($projectRoot !== null) {
            $this->info("Project root for MCP config: {$projectRoot}");
        }

        // Install for the detected/specified IDE
        $success = false;
        switch ($normalizedIde) {
            case 'cursor':
                $success = $this->installForCursor($eeCliPath, $serverName, $projectRoot);
                break;
            case 'claude':
                $success = $this->installForClaude($eeCliPath, $serverName, $projectRoot);
                break;
            case 'codex':
                $success = $this->installForCodex($eeCliPath, $serverName);
                break;
            default:
                $this->info("Unknown IDE: {$ide}. Attempting Cursor installation...");
                $success = $this->installForCursor($eeCliPath, $serverName, $projectRoot);
                break;
        }

        if ($success) {
            $this->info('');
            $this->info('✓ MCP server configuration installed successfully!');
            $this->info('');
            $this->info('Next steps:');
            $this->info('1. Restart your IDE to pick up the new MCP server configuration');

            // Determine server name based on IDE
            $this->info("2. The MCP server will be available as \"{$serverName}\"");
            $this->info('');

            // Determine command based on DDEV detection
            $cmdConfig = $this->buildCommandConfig($eeCliPath);
            $commandLine = $cmdConfig['command'].' '.implode(' ', $cmdConfig['args']);
            $this->info('To start the server manually, run:');
            $this->info("  {$commandLine}");
            $this->info('');

            return 0;
        } else {
            $this->fail('Failed to install MCP server configuration.');

            return 1;
        }
    }

    /**
     * Resolve final IDE selection for installation.
     * Preference order:
     * 1) Explicit CLI flags/options
     * 2) Interactive prompt (TTY only, when no explicit selection)
     * 3) Auto-detection fallback
     */
    protected function resolveInstallTargetIde(): string
    {
        $args = $_SERVER['argv'] ?? [];
        $ide = $this->resolveIdeOption();

        if ($ide === 'auto' && ! $this->hasIdeSelectionArgument($args) && $this->canPromptForIdeSelection()) {
            $ide = $this->promptForIdeSelection();
        }

        if ($ide === 'auto') {
            return $this->detectIde();
        }

        return $ide;
    }

    /**
     * Resolve desired MCP server name from CLI options.
     * Defaults are IDE-specific for backward compatibility.
     */
    protected function resolveServerNameOption(string $normalizedIde): string
    {
        $argvName = $this->parseServerNameFromArgv($_SERVER['argv'] ?? []);
        if ($argvName !== null && $argvName !== '') {
            return $argvName;
        }

        if (method_exists($this, 'option')) {
            $fromServerName = $this->option('--server-name', null);
            if (is_string($fromServerName) && $fromServerName !== '') {
                return $fromServerName;
            }

            $fromAlias = $this->option('--name', null);
            if (is_string($fromAlias) && $fromAlias !== '') {
                return $fromAlias;
            }
        }

        if ($normalizedIde === 'claude') {
            return 'expressionengine';
        }

        return 'expressionengine-mcp';
    }

    /**
     * Resolve install project root for project-scoped IDEs.
     * Preference order:
     * 1) --project-root option
     * 2) Interactive prompt (TTY only, when confirmations are not skipped)
     * 3) Default detected root
     */
    protected function resolveInstallProjectRoot(string $defaultRoot): string
    {
        $fromArgs = $this->parseProjectRootFromArgv($_SERVER['argv'] ?? []);
        if ($fromArgs !== null && $fromArgs !== '') {
            return $this->normalizePathInput($fromArgs);
        }

        if (method_exists($this, 'option')) {
            $fromOption = $this->option('--project-root', null);
            if (is_string($fromOption) && $fromOption !== '') {
                return $this->normalizePathInput($fromOption);
            }
        }

        if ($this->shouldSkipConfirmation() || ! $this->canPromptForIdeSelection()) {
            return $defaultRoot;
        }

        return $this->promptForProjectRoot($defaultRoot);
    }

    /**
     * Parse project root from raw argv.
     *
     * @param  array<int, string>  $args
     */
    protected function parseProjectRootFromArgv(array $args): ?string
    {
        foreach ($args as $arg) {
            if (strpos($arg, '--project-root=') === 0) {
                $value = substr($arg, 15);

                return $value === '' ? null : $value;
            }
        }

        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            if ($args[$i] !== '--project-root') {
                continue;
            }

            $value = $args[$i + 1] ?? '';

            return $value === '' ? null : $value;
        }

        return null;
    }

    /**
     * Prompt for project root path, defaulting to detected root.
     */
    protected function promptForProjectRoot(string $defaultRoot): string
    {
        $this->info('');
        $this->info("Project root for local MCP config [{$defaultRoot}]:");

        $maxAttempts = 3;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $input = $this->readLineFromStdin();
            if ($input === null || trim($input) === '') {
                return $defaultRoot;
            }

            $candidate = $this->normalizePathInput($input);
            if (is_dir($candidate)) {
                return $candidate;
            }

            $this->info("Directory does not exist: {$candidate}");
            $this->info("Enter a valid directory path or press Enter for default [{$defaultRoot}]:");
        }

        return $defaultRoot;
    }

    /**
     * Normalize a path from user input to an absolute-like path when possible.
     */
    protected function normalizePathInput(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return $trimmed;
        }

        if (strpos($trimmed, '~') === 0) {
            $home = getenv('HOME');
            if (is_string($home) && $home !== '') {
                $trimmed = rtrim($home, '/\\').substr($trimmed, 1);
            }
        }

        $resolved = realpath($trimmed);
        if ($resolved !== false) {
            return rtrim($resolved, '/\\');
        }

        if ($this->isAbsolutePath($trimmed)) {
            return rtrim($trimmed, '/\\');
        }

        $cwd = getcwd();
        if ($cwd !== false && $cwd !== '') {
            return rtrim($cwd, '/\\').'/'.ltrim($trimmed, '/\\');
        }

        return rtrim($trimmed, '/\\');
    }

    /**
     * Determine whether a path is absolute on Unix or Windows.
     */
    protected function isAbsolutePath(string $path): bool
    {
        return strpos($path, '/') === 0 || preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
    }

    /**
     * Parse server name from raw argv.
     *
     * @param  array<int, string>  $args
     */
    protected function parseServerNameFromArgv(array $args): ?string
    {
        foreach ($args as $arg) {
            if (strpos($arg, '--server-name=') === 0) {
                $value = substr($arg, 14);

                return $value === '' ? null : $value;
            }

            if (strpos($arg, '--name=') === 0) {
                $value = substr($arg, 7);

                return $value === '' ? null : $value;
            }
        }

        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            if ($args[$i] === '--server-name' || $args[$i] === '--name') {
                $value = $args[$i + 1] ?? '';

                return $value === '' ? null : $value;
            }
        }

        return null;
    }

    /**
     * Validate server names used as MCP keys across supported IDE config files.
     */
    protected function isValidServerName(string $serverName): bool
    {
        return preg_match('/^[A-Za-z0-9._-]+$/', $serverName) === 1;
    }

    /**
     * Resolve the requested IDE from command options.
     * Supports both --ide=<name> and explicit flags like --cursor.
     */
    protected function resolveIdeOption(): string
    {
        // Always parse raw argv first so aliases like --cursor/--claude/--codex
        // work even if the underlying CLI option parser handles boolean flags differently.
        $argvIde = $this->parseIdeFromArgv($_SERVER['argv'] ?? []);
        if ($argvIde !== 'auto') {
            return $argvIde;
        }

        if (method_exists($this, 'option')) {
            $flagMap = [
                'cursor' => 'cursor',
                'claude' => 'claude',
                'codex' => 'codex',
            ];

            foreach ($flagMap as $flag => $value) {
                $selected = $this->option('--'.$flag, false);
                if ($selected === true || $selected === 1 || $selected === '1') {
                    return $value;
                }
            }

            $ide = $this->option('--ide', 'auto');
            if (is_string($ide) && $ide !== '') {
                return $ide;
            }
        }

        return 'auto';
    }

    /**
     * Fallback argument parser for environments where option() is unavailable.
     *
     * @param  array<int, string>  $args
     */
    protected function parseIdeFromArgv(array $args): string
    {
        $flagMap = [
            '--cursor' => 'cursor',
            '--claude' => 'claude',
            '--codex' => 'codex',
        ];

        foreach ($flagMap as $flag => $value) {
            if (in_array($flag, $args, true)) {
                return $value;
            }
        }

        foreach ($args as $arg) {
            if (strpos($arg, '--ide=') === 0) {
                $ide = substr($arg, 6);

                return $ide === '' ? 'auto' : $ide;
            }
        }

        $count = count($args);
        for ($i = 0; $i < $count; $i++) {
            if ($args[$i] !== '--ide') {
                continue;
            }

            $value = $args[$i + 1] ?? '';

            return $value === '' ? 'auto' : $value;
        }

        return 'auto';
    }

    /**
     * Check whether argv includes an explicit IDE selection flag.
     *
     * @param  array<int, string>  $args
     */
    protected function hasIdeSelectionArgument(array $args): bool
    {
        foreach ($args as $arg) {
            if (in_array($arg, ['--cursor', '--claude', '--codex', '--ide'], true)) {
                return true;
            }

            if (strpos($arg, '--ide=') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if interactive prompting is possible.
     */
    protected function canPromptForIdeSelection(): bool
    {
        if (! defined('STDIN')) {
            return false;
        }

        if (function_exists('stream_isatty')) {
            return stream_isatty(STDIN);
        }

        if (function_exists('posix_isatty')) {
            return posix_isatty(STDIN);
        }

        return false;
    }

    /**
     * Prompt the user to choose an IDE/agent for configuration.
     */
    protected function promptForIdeSelection(): string
    {
        $this->info('Select an IDE/agent to configure:');
        $this->info('  1) Cursor');
        $this->info('  2) Claude Code');
        $this->info('  3) Codex');
        $this->info('  4) Auto-detect');
        $this->info('Enter choice (1-4, default 4):');

        $maxAttempts = 3;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $input = $this->readLineFromStdin();
            if ($input === null || $input === '') {
                return 'auto';
            }

            $mapped = $this->mapIdeSelectionInput($input);
            if ($mapped !== null) {
                return $mapped;
            }

            $this->info('Invalid choice. Enter 1, 2, 3, or 4 (or press Enter for auto-detect):');
        }

        return 'auto';
    }

    /**
     * Read one line from STDIN.
     */
    protected function readLineFromStdin(): ?string
    {
        if (! defined('STDIN')) {
            return null;
        }

        $line = fgets(STDIN);
        if ($line === false) {
            return null;
        }

        return trim($line);
    }

    /**
     * Map user-entered selection text to canonical IDE names.
     */
    protected function mapIdeSelectionInput(string $input): ?string
    {
        $normalized = strtolower(trim($input));

        $map = [
            '1' => 'cursor',
            'cursor' => 'cursor',
            '2' => 'claude',
            'claude' => 'claude',
            'claude code' => 'claude',
            'claudecode' => 'claude',
            '3' => 'codex',
            'codex' => 'codex',
            '4' => 'auto',
            'auto' => 'auto',
            'a' => 'auto',
        ];

        return $map[$normalized] ?? null;
    }

    /**
     * Detect which IDE is being used
     */
    protected function detectIde(): string
    {
        // Check for Cursor-specific environment variables or files
        if (getenv('CURSOR_VERSION') !== false || file_exists(getcwd().'/.cursor')) {
            return 'cursor';
        }

        // Check for project-local Codex config
        if (file_exists(getcwd().'/.codex') || file_exists(getcwd().'/.codex/config.toml')) {
            return 'codex';
        }

        // Check for VS Code (Claude Code extension uses VS Code)
        if (getenv('VSCODE_PID') !== false || getenv('VSCODE_INJECTION') !== false || file_exists(getcwd().'/.vscode')) {
            return 'claude';
        }

        // Check for Claude Desktop (standalone app)
        if (getenv('ANTHROPIC_API_KEY') !== false) {
            return 'claude';
        }

        // Default to Cursor as it's the most common
        return 'cursor';
    }

    /**
     * Get the ExpressionEngine CLI path
     */
    protected function getEeCliPath(): ?string
    {
        // Check if we're running from within EE (BASEPATH should be defined)
        if (defined('BASEPATH') && defined('SYSPATH')) {
            // We're inside EE, construct the path
            $sysPath = SYSPATH;
            $eeCliPath = $sysPath.'ee/eecli.php';

            if (file_exists($eeCliPath)) {
                return $eeCliPath;
            }
        }

        // Try to find eecli.php relative to current working directory
        $cwd = getcwd();
        $possiblePaths = [
            $cwd.'/system/ee/eecli.php',
            $cwd.'/../system/ee/eecli.php',
            $cwd.'/../../system/ee/eecli.php',
        ];

        foreach ($possiblePaths as $path) {
            $resolved = realpath($path);
            if ($resolved !== false && file_exists($resolved)) {
                return $resolved;
            }
        }

        return null;
    }

    /**
     * Build command configuration based on environment (DDEV detection)
     *
     * @return array Array with 'command' and 'args' keys
     */
    protected function buildCommandConfig(string $eeCliPath): array
    {
        // Only use DDEV if we're actually running inside a DDEV container
        // Check for DDEV_PROJECT environment variable (set when inside DDEV container)
        // Don't just check for .ddev/config.yaml file existence, as that doesn't mean
        // we're actively using DDEV - the user might have DDEV installed but not be using it
        if (getenv('DDEV_PROJECT') !== false) {
            // Use ddev command to run through DDEV container
            return [
                'command' => 'ddev',
                'args' => ['ee', 'mcp:serve'],
            ];
        }

        // Standard PHP command (default)
        return [
            'command' => 'php',
            'args' => array_merge(
                $this->getStdioSafetyPhpArgs(),
                [
                    $eeCliPath,
                    'mcp:serve',
                ]
            ),
        ];
    }

    /**
     * PHP arguments that suppress CLI output noise that can corrupt MCP stdio transport.
     *
     * @return array<int, string>
     */
    protected function getStdioSafetyPhpArgs(): array
    {
        return [
            '-d',
            'display_errors=0',
            '-d',
            'html_errors=0',
        ];
    }

    /**
     * Install MCP configuration for Cursor
     */
    protected function installForCursor(string $eeCliPath, ?string $serverName = null, ?string $projectRoot = null): bool
    {
        $serverName = $serverName ?: 'expressionengine-mcp';
        $projectRoot = $projectRoot === null || $projectRoot === ''
            ? $this->resolveProjectRoot($eeCliPath)
            : $this->normalizePathInput($projectRoot);
        $cursorDir = $projectRoot.'/.cursor';
        $configFile = $cursorDir.'/mcp.json';

        // Create .cursor directory if it doesn't exist
        if (! is_dir($cursorDir)) {
            if (! mkdir($cursorDir, 0755, true)) {
                $this->fail("Could not create directory: {$cursorDir}");

                return false;
            }
            $this->info("Created directory: {$cursorDir}");
        }

        // Read existing config or create new one
        $config = [];
        if (file_exists($configFile)) {
            $existing = file_get_contents($configFile);
            if ($existing !== false) {
                $config = json_decode($existing, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->info('Existing mcp.json has invalid JSON. Creating new configuration.');
                    $config = [];
                }
            }
        }

        // Ensure mcpServers key exists
        if (! isset($config['mcpServers'])) {
            $config['mcpServers'] = [];
        }

        // Build command configuration (handles DDEV detection)
        $cmdConfig = $this->buildCommandConfig($eeCliPath);

        // Add or update ExpressionEngine MCP server configuration
        $config['mcpServers'][$serverName] = [
            'type' => 'stdio',
            'command' => $cmdConfig['command'],
            'args' => $cmdConfig['args'],
        ];

        // Write the configuration file
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->fail('Failed to encode configuration to JSON.');

            return false;
        }

        if (file_put_contents($configFile, $json) === false) {
            $this->fail("Could not write configuration file: {$configFile}");

            return false;
        }

        $this->info("✓ Configuration written to: {$configFile}");

        // Copy rule files
        $this->copyRulesFiles($cursorDir.'/rules');

        return true;
    }

    /**
     * Install MCP configuration for Claude (VS Code extension or Desktop)
     */
    protected function installForClaude(string $eeCliPath, ?string $serverName = null, ?string $projectRoot = null): bool
    {
        $serverName = $serverName ?: 'expressionengine';
        $projectRoot = $projectRoot === null || $projectRoot === ''
            ? $this->resolveProjectRoot($eeCliPath)
            : $this->normalizePathInput($projectRoot);
        $configFile = $projectRoot.'/.mcp.json';
        $settingsFile = $projectRoot.'/.claude/settings.local.json';

        // Read existing config or create new one
        $config = [];
        if (file_exists($configFile)) {
            $existing = file_get_contents($configFile);
            if ($existing !== false) {
                $config = json_decode($existing, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $this->info('Existing .mcp.json has invalid JSON. Creating new configuration.');
                    $config = [];
                }
            }
        }

        // Ensure mcpServers key exists
        if (! isset($config['mcpServers'])) {
            $config['mcpServers'] = [];
        }

        // Build command configuration (handles DDEV detection)
        $cmdConfig = $this->buildCommandConfig($eeCliPath);

        // Add or update ExpressionEngine MCP server configuration
        $config['mcpServers'][$serverName] = [
            'command' => $cmdConfig['command'],
            'args' => $cmdConfig['args'],
            'env' => (object) [],
        ];

        // Write the configuration file
        $json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->fail('Failed to encode configuration to JSON.');

            return false;
        }

        if (file_put_contents($configFile, $json) === false) {
            $this->fail("Could not write configuration file: {$configFile}");

            return false;
        }

        $this->info("✓ Configuration written to: {$configFile}");

        // Enable the MCPJsonServer in ./claude/settings.local.json
        $settings = [];
        if (file_exists($settingsFile)) {
            $existing = file_get_contents($settingsFile);
            if ($existing !== false) {
                $settings = json_decode($existing, true);
                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($settings)) {
                    $this->info('Existing ./claude/settings.local.json has invalid JSON. Backing up current settings and creating new configuration.');
                    // backup original settings
                    file_put_contents($settingsFile.'.bak', $existing);
                    $settings = [];
                }
            }
        }

        if (! is_array($settings['enabledMcpjsonServers'] ?? null)) {
            $settings['enabledMcpjsonServers'] = [];
        }

        if (! in_array($serverName, $settings['enabledMcpjsonServers'], true)) {
            $settings['enabledMcpjsonServers'][] = $serverName;
        }

        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $this->fail('Failed to encode configuration to JSON.');

            return false;
        }

        if (! is_dir($projectRoot.'/.claude')) {
            if (! mkdir($projectRoot.'/.claude', 0755, true) && ! is_dir($projectRoot.'/.claude')) {
                $this->fail("Could not create directory: {$projectRoot}/.claude");

                return false;
            }
        }

        if (file_put_contents($settingsFile, $json) === false) {
            $this->fail("Could not write settings file: {$settingsFile}");

            return false;
        }

        return true;
    }

    /**
     * Resolve EE project root path from the known eecli.php location.
     * Expected layout: <project>/system/ee/eecli.php
     */
    protected function resolveProjectRoot(string $eeCliPath): string
    {
        $projectRoot = dirname(dirname(dirname($eeCliPath)));
        $resolved = realpath($projectRoot);

        if ($resolved !== false && is_dir($resolved)) {
            return rtrim($resolved, '/\\');
        }

        if ($projectRoot !== '' && is_dir($projectRoot)) {
            return rtrim($projectRoot, '/\\');
        }

        $cwd = getcwd();
        if ($cwd !== false && is_dir($cwd)) {
            return rtrim($cwd, '/\\');
        }

        return '.';
    }

    /**
     * Install MCP configuration for Codex (global ~/.codex/config.toml)
     */
    protected function installForCodex(string $eeCliPath, ?string $serverName = null): bool
    {
        $serverName = $serverName ?: 'expressionengine-mcp';
        $configFile = $this->getCodexGlobalConfigPath();
        if ($configFile === null) {
            $this->fail('Could not determine Codex global config path. Ensure your HOME directory is available.');

            return false;
        }
        $codexDir = dirname($configFile);

        if (! $this->confirmCodexGlobalInstall($configFile)) {
            $this->fail('Installation cancelled by user.');

            return false;
        }

        if (! is_dir($codexDir)) {
            if (! mkdir($codexDir, 0755, true)) {
                $this->fail("Could not create directory: {$codexDir}");

                return false;
            }
            $this->info("Created directory: {$codexDir}");
        }

        $existingToml = '';
        if (file_exists($configFile)) {
            $existing = file_get_contents($configFile);
            if ($existing === false) {
                $this->fail("Could not read configuration file: {$configFile}");

                return false;
            }
            $existingToml = $existing;
        }

        $cmdConfig = $this->buildCommandConfig($eeCliPath);
        $updatedToml = $this->upsertCodexServerToml($existingToml, $serverName, $cmdConfig);

        if (file_put_contents($configFile, $updatedToml) === false) {
            $this->fail("Could not write configuration file: {$configFile}");

            return false;
        }

        $this->info("✓ Configuration written to: {$configFile}");

        return true;
    }

    /**
     * Ask for explicit confirmation before writing global Codex config.
     * Can be bypassed with --yes / -y.
     */
    protected function confirmCodexGlobalInstall(string $configFile): bool
    {
        if ($this->shouldSkipConfirmation()) {
            return true;
        }

        if (! $this->canPromptForIdeSelection()) {
            $this->info('Codex install targets a global config file and requires confirmation.');
            $this->info('Re-run with --yes to allow non-interactive installation.');

            return false;
        }

        $this->info('');
        $this->info('Codex installation target:');
        $this->info("  {$configFile}");
        $this->info('This will update your global Codex MCP configuration.');
        $this->info('Proceed? [y/N]');

        $input = $this->readLineFromStdin();
        if ($input === null) {
            return false;
        }

        $normalized = strtolower(trim($input));

        return in_array($normalized, ['y', 'yes'], true);
    }

    /**
     * Determine whether confirmation prompts should be skipped.
     */
    protected function shouldSkipConfirmation(): bool
    {
        $args = $_SERVER['argv'] ?? [];
        if (in_array('--yes', $args, true) || in_array('-y', $args, true)) {
            return true;
        }

        if (method_exists($this, 'option')) {
            $yes = $this->option('--yes', false);
            if ($yes === true || $yes === 1 || $yes === '1') {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the global Codex config file path (~/.codex/config.toml).
     */
    protected function getCodexGlobalConfigPath(): ?string
    {
        $home = getenv('HOME');
        if (! is_string($home) || trim($home) === '') {
            return null;
        }

        return rtrim($home, '/\\').'/.codex/config.toml';
    }

    /**
     * Insert or replace a Codex MCP server definition in config.toml content.
     *
     * @param  array{command: string, args: array<int, string>}  $cmdConfig
     */
    protected function upsertCodexServerToml(string $toml, string $serverName, array $cmdConfig): string
    {
        $cleanToml = $this->stripCodexServerTables($toml, $serverName);

        $args = $cmdConfig['args'] ?? [];
        $quotedArgs = array_map([$this, 'tomlString'], $args);
        $serverBlock = '[mcp_servers.'.$this->tomlString($serverName)."]\n"
            .'command = '.$this->tomlString($cmdConfig['command'])."\n"
            .'args = ['.implode(', ', $quotedArgs)."]\n";

        $trimmed = rtrim($cleanToml);
        if ($trimmed === '') {
            return $serverBlock;
        }

        return $trimmed."\n\n".$serverBlock;
    }

    /**
     * Remove all table sections related to a specific server:
     * [mcp_servers.<name>] and nested sections such as [mcp_servers.<name>.env].
     */
    protected function stripCodexServerTables(string $toml, string $serverName): string
    {
        if ($toml === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $toml);
        if (! is_array($lines)) {
            return $toml;
        }

        $kept = [];
        $skippingTargetBlock = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            $isHeader = preg_match('/^\[(.+)\](?:\s+#.*)?$/', $trimmed) === 1;

            if ($isHeader) {
                $isTarget = $this->isCodexServerTableHeader($trimmed, $serverName);
                if ($isTarget) {
                    $skippingTargetBlock = true;

                    continue;
                }

                // We reached a different table, so stop skipping.
                $skippingTargetBlock = false;
            }

            if ($skippingTargetBlock) {
                continue;
            }

            $kept[] = $line;
        }

        $result = implode("\n", $kept);
        if ($toml !== '' && str_ends_with($toml, "\n")) {
            $result .= "\n";
        }

        return $result;
    }

    /**
     * Determine whether a TOML table header belongs to the target MCP server.
     */
    protected function isCodexServerTableHeader(string $trimmedHeader, string $serverName): bool
    {
        $header = preg_replace('/\s+#.*$/', '', $trimmedHeader);
        if (! is_string($header)) {
            return false;
        }

        $serverPattern = preg_quote($serverName, '/');

        return preg_match('/^\[\s*mcp_servers\s*\.\s*(?:"'.$serverPattern.'"|'.$serverPattern.')(?:\s*\.|\s*\])/', $header) === 1;
    }

    /**
     * Encode a PHP string as a TOML basic string.
     */
    protected function tomlString(string $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            // Fallback to a minimal escaped representation.
            $fallback = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);

            return '"'.$fallback.'"';
        }

        return $encoded;
    }

    /**
     * Copy MCP rule files to the target rules directory
     */
    protected function copyRulesFiles(string $targetRulesDir): void
    {
        // Get the addon path
        $addonPath = $this->getAddonPath();
        if (! $addonPath) {
            $this->info('Warning: Could not locate addon path. Skipping rule file copy.');

            return;
        }

        $sourceRuleFile = $addonPath.'/rules/using-expressionengine-mcp-server.mdc';

        // Check if source file exists
        if (! file_exists($sourceRuleFile)) {
            $this->info("Warning: Rule file not found at {$sourceRuleFile}. Skipping rule file copy.");

            return;
        }

        // Create target rules directory if it doesn't exist
        if (! is_dir($targetRulesDir)) {
            if (! mkdir($targetRulesDir, 0755, true)) {
                $this->info("Warning: Could not create rules directory: {$targetRulesDir}. Skipping rule file copy.");

                return;
            }
            $this->info("Created directory: {$targetRulesDir}");
        }

        // Copy the rule file
        $targetRuleFile = $targetRulesDir.'/using-expressionengine-mcp-server.mdc';
        if (copy($sourceRuleFile, $targetRuleFile)) {
            $this->info("✓ Rule file copied to: {$targetRuleFile}");
        } else {
            $this->info("Warning: Could not copy rule file to {$targetRuleFile}. You may need to copy it manually.");
        }
    }

    /**
     * Get the addon installation path
     */
    protected function getAddonPath(): ?string
    {
        // Check if we're running from within EE (BASEPATH should be defined)
        if (defined('BASEPATH') && defined('SYSPATH')) {
            // We're inside EE, construct the path
            $sysPath = SYSPATH;
            $addonPath = $sysPath.'../user/addons/mcp';

            $resolved = realpath($addonPath);
            if ($resolved !== false && is_dir($resolved)) {
                return $resolved;
            }
        }

        // Try to find addon path relative to current working directory
        $cwd = getcwd();
        $possiblePaths = [
            $cwd.'/system/user/addons/mcp',
            $cwd.'/../system/user/addons/mcp',
            $cwd.'/../../system/user/addons/mcp',
            // Also check if we're already in the addon directory
            $cwd,
        ];

        foreach ($possiblePaths as $path) {
            $resolved = realpath($path);
            if ($resolved !== false && is_dir($resolved) && file_exists($resolved.'/rules/using-expressionengine-mcp-server.mdc')) {
                return $resolved;
            }
        }

        return null;
    }
}
