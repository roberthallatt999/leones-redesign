# ExpressionEngine MCP Server Addon

This addon provides Model Context Protocol (MCP) server functionality for ExpressionEngine, allowing AI assistants like Codex, Claude, Cursor, and GitHub Copilot to interact with your EE installation.

## Features

- **Global MCP Server**: Aggregates MCP elements from all installed addons into a single server
- **Dual Registration**: Supports both automatic discovery and explicit class lists
- **Settings Management**: Enable/disable elements, manage categories and permissions via CP
- **Base Classes**: Developer-friendly abstract classes for tools, resources, and prompts
- **Schema Helpers**: Fluent API for defining input/output schemas
- **Real-time Updates**: Automatic notifications when settings change
- **Field Scaffold Support**: CLI-backed field template structure generation for complex EE fields

## Installation

1. Install the addon via EE's addon manager or composer
2. The addon will automatically register its MCP server
3. Start the server with: `php eecli.php mcp:serve`

## Quick Start

### 1. Create a Simple Tool

Create a new addon with MCP elements:

```bash
# Create addon structure
mkdir -p system/user/addons/myaddon/Mcp/Tools
mkdir -p system/user/addons/myaddon/Mcp/Resources
mkdir -p system/user/addons/myaddon/Mcp/Prompts
```

Create `system/user/addons/myaddon/Mcp/Tools/HelloTool.php`:

```php
<?php

namespace MyAddon\Mcp\Tools;

use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;

#[EeCategory('developer')]
class HelloTool extends AbstractTool
{
    public function description(): string
    {
        return 'Returns a personalized greeting';
    }

    public function schema(): array
    {
        return [
            'name' => [
                'type' => 'string',
                'description' => 'The name to greet',
                'minLength' => 1,
                'maxLength' => 100,
            ],
            'style' => [
                'type' => 'string',
                'enum' => ['formal', 'casual', 'excited'],
                'default' => 'casual',
                'description' => 'Greeting style',
            ],
        ];
    }

    public function handle(array $arguments): array
    {
        $name = $arguments['name'] ?? 'World';
        $style = $arguments['style'] ?? 'casual';

        $greeting = match($style) {
            'formal' => "Greetings, {$name}.",
            'excited' => "Hello {$name}!!!",
            default => "Hey {$name}!",
        };

        return [['role' => 'user', 'content' => $greeting]];
    }
}
```

### 2. Configure Your Addon

Update `system/user/addons/myaddon/addon.setup.php`:

```php
return [
    'name' => 'My Addon',
    'description' => 'My custom addon with MCP tools',
    'version' => '1.0.0',
    'author' => 'Your Name',
    // ... other standard fields ...

    'mcp' => [
        // Auto-discovery from Mcp/ directory
        'scan' => ['Mcp'],
        'exclude' => ['tests', 'vendor'],

        // Explicit class registration (optional)
        'tools' => [
            MyAddon\Mcp\Tools\HelloTool::class,
        ],

        // Enable MCP for this addon
        'enabled' => true,
    ],
];
```

### 3. Start the Server

```bash
php eecli.php mcp:serve
```

The server will automatically discover your `HelloTool` and make it available to MCP clients.

## Registration Methods

### Method 1: Automatic Discovery (Recommended)

Place your MCP classes in the `Mcp/` directory with standard naming:

```
myaddon/
├── Mcp/
│   ├── Tools/
│   │   ├── WeatherTool.php
│   │   └── SearchTool.php
│   ├── Resources/
│   │   └── ConfigResource.php
│   └── Prompts/
│       └── HelpPrompt.php
```

The server will automatically find and register these classes.

### Method 2: Explicit Registration

For more control, explicitly list classes in `addon.setup.php`:

```php
'mcp' => [
    'tools' => [
        MyAddon\Mcp\Tools\WeatherTool::class,
        ['class' => MyAddon\Mcp\Tools\SearchTool::class, 'enabled' => true],
    ],
    'resources' => [
        MyAddon\Mcp\Resources\ConfigResource::class,
    ],
    'prompts' => [
        MyAddon\Mcp\Prompts\HelpPrompt::class,
    ],
],
```

## MCP Element Types

### Tools

Tools perform actions and return results. Extend `AbstractTool`:

```php
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;

class MyTool extends AbstractTool
{
    public function description(): string
    {
        return 'What this tool does';
    }

    public function schema(): array
    {
        // Define input parameters
        return [
            'param1' => ['type' => 'string', 'description' => 'Parameter description'],
        ];
    }

    public function handle(array $arguments): array
    {
        // Process arguments and return response
        return [['role' => 'user', 'content' => 'Tool result']];
    }
}
```

### Resources

Resources provide data access. Extend `AbstractResource`:

```php
use ExpressionEngine\Addons\Mcp\Support\AbstractResource;

class MyResource extends AbstractResource
{
    public function uri(): string
    {
        return 'data://my/resource';
    }

    public function mimeType(): string
    {
        return 'application/json';
    }

    public function fetch(array $params = []): mixed
    {
        // Return resource data
        return ['key' => 'value'];
    }
}
```

### Prompts

Prompts generate conversation starters. Extend `AbstractPrompt`:

```php
use ExpressionEngine\Addons\Mcp\Support\AbstractPrompt;

class MyPrompt extends AbstractPrompt
{
    public function description(): string
    {
        return 'What this prompt does';
    }

    public function arguments(): array
    {
        return [
            'topic' => ['type' => 'string', 'description' => 'Prompt topic'],
        ];
    }

    public function handle(array $arguments): array
    {
        return [
            ['role' => 'user', 'content' => "Tell me about {$arguments['topic']}"],
        ];
    }
}
```

## Categories and Permissions

### Categories

Group related elements using the `EeCategory` attribute:

```php
use ExpressionEngine\Addons\Mcp\Attributes\EeCategory;

#[EeCategory('content')]
class ContentTool extends AbstractTool
{
    // Tool implementation
}
```

### Permissions

Control access with the `EePermissions` attribute:

```php
use ExpressionEngine\Addons\Mcp\Attributes\EePermissions;

#[EePermissions(['role:Developer', 'can:manage_content'])]
class AdminTool extends AbstractTool
{
    // Tool implementation
}
```

Permission formats:
- `role:Name` - EE role/membership group
- `can:permission` - Custom permission
- `user:123` - Specific user ID

## Settings Management

Access MCP settings via the EE Control Panel under Add-ons > MCP.

### Element Management
- View all registered MCP elements
- Enable/disable individual elements
- Filter by addon, category, or type

### Category Management
- Create/edit categories
- Set category permissions
- Bulk enable/disable categories

### Permission Management
- Configure role-based access
- Set element-specific permissions
- Override category defaults

## Schema Definition

### Using PHP Attributes (Simple)

```php
use Mcp\Capability\Attribute\Schema;

public function handle(
    #[Schema(description: 'Search query')]
    string $query,

    #[Schema(description: 'Result limit', minimum: 1, maximum: 100, default: 10)]
    int $limit = 10
): array {
    // Implementation
}
```

### Using Schema Builder (Complex)

```php
use ExpressionEngine\Addons\Mcp\Support\Schema;

public function schema(): array
{
    $schema = new Schema();

    return [
        'config' => $schema->object([
            'database' => $schema->object([
                'host' => $schema->string()->format('hostname'),
                'port' => $schema->integer(1, 65535)->default(3306),
            ], ['host']),
        ], ['database']),
    ];
}
```

See `SCHEMA_GUIDE.md` for detailed schema documentation.

## Advanced Features

### Custom Providers

For complex registration logic, create a provider class:

```php
use ExpressionEngine\Addons\Mcp\Contracts\Provider;
use ExpressionEngine\Addons\Mcp\Services\Registrar;

class MyProvider implements Provider
{
    public function register(Registrar $registrar): void
    {
        if (ee()->config->item('enable_advanced_tools')) {
            $registrar->discover(__DIR__, ['Advanced']);
        }
    }
}
```

Then reference it in `addon.setup.php`:

```php
'mcp' => [
    'provider' => MyAddon\Mcp\MyProvider::class,
],
```

### Real-time Updates

When settings change, the server automatically notifies MCP clients of available element changes.

## Configuration Options

Full `addon.setup.php` MCP configuration:

```php
'mcp' => [
    // Discovery settings
    'scan' => ['Mcp', 'src/Mcp'],           // Directories to scan
    'exclude' => ['tests', 'vendor'],       // Directories to skip
    'prefix' => 'myaddon',                  // Element name prefix

    // Explicit registration
    'tools' => [/* class names or arrays */],
    'resources' => [/* class names or arrays */],
    'prompts' => [/* class names or arrays */],

    // Conditional settings
    'provider' => MyProvider::class,        // Custom provider
    'enabled' => true,                      // Enable/disable MCP for addon
],
```

## Troubleshooting

### Server Won't Start
- Check that `mcp/sdk` is installed via composer
- Verify addon.setup.php syntax
- Check EE logs for errors

### Elements Not Found
- Ensure classes are autoloadable
- Check namespace and file paths
- Verify MCP block in addon.setup.php

### Permission Issues
- Check role assignments in EE
- Verify permission syntax in attributes
- Review MCP settings in CP

## Examples

See the `examples/` directory for complete working examples of:
- Simple tools and resources
- Category and permission usage
- Complex schemas
- Custom providers

## Contributing

When developing MCP addons:
1. Follow the directory structure conventions
2. Use the provided base classes
3. Test with the MCP Inspector: `php artisan mcp:inspector`
4. Document your elements clearly

## License

This addon is licensed under the same terms as ExpressionEngine.
