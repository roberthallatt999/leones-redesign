# MCP Schema Guide for ExpressionEngine

This guide explains how to define schemas for your MCP tools, resources, and prompts in ExpressionEngine addons.

## Schema Definition Options

ExpressionEngine MCP supports two approaches for defining schemas:

1. **PHP Attributes** (recommended for simple schemas)
2. **Schema Builder API** (recommended for complex schemas)

## Using PHP Attributes

The official MCP PHP SDK (`mcp/sdk`) supports parameter-level `#[Schema]` attributes for automatic schema generation:

```php
use Mcp\Capability\Attribute\Schema;
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;

class WeatherTool extends AbstractTool
{
    public function schema(): array
    {
        // Return empty array when using attributes
        return [];
    }

    public function handle(
        #[Schema(description: 'The location to get weather for')]
        string $location,

        #[Schema(
            description: 'Temperature units',
            enum: ['celsius', 'fahrenheit'],
            default: 'celsius'
        )]
        string $units = 'celsius'
    ): array {
        // Your tool logic here
        return [['role' => 'user', 'content' => "Weather data for {$location} in {$units}"]];
    }
}
```

### Available Schema Attribute Properties

- `description`: Human-readable description
- `title`: Human-readable title
- `default`: Default value
- `enum`: Array of allowed values
- `minimum`/`maximum`: Numeric constraints
- `minLength`/`maxLength`: String length constraints
- `pattern`: Regular expression pattern for strings
- `format`: Predefined format (email, uri, date, etc.)

## Using Schema Builder API

For complex schemas, use the fluent Schema Builder API:

```php
use ExpressionEngine\Addons\Mcp\Support\AbstractTool;
use ExpressionEngine\Addons\Mcp\Support\Schema;

class ComplexTool extends AbstractTool
{
    public function schema(): array
    {
        $schema = new Schema();

        return [
            'user' => $schema->object([
                    'name' => $schema->string(1, 100)->description('User full name'),
                    'email' => $schema->string()->format('email')->description('Email address'),
                    'age' => $schema->integer(18, 120)->description('Age in years'),
                ], ['name', 'email'])  // required fields
                ->description('User information'),

            'preferences' => $schema->object([
                    'theme' => $schema->enum(['light', 'dark'])->default('light'),
                    'notifications' => $schema->boolean()->default(true),
                ])
                ->description('User preferences'),

            'tags' => $schema->array(
                    $schema->string()->minLength(1)->maxLength(50)
                )
                ->description('User tags')
                ->minItems(0)
                ->maxItems(10),
        ];
    }
}
```

## Schema Builder Methods

### Basic Types
- `$schema->string($minLength, $maxLength, $pattern, $format)`
- `$schema->integer($minimum, $maximum)`
- `$schema->number($minimum, $maximum)`
- `$schema->boolean()`
- `$schema->enum(['value1', 'value2'])`

### Complex Types
- `$schema->array($itemsSchema, $minItems, $maxItems)`
- `$schema->object($properties, $required)`

### Fluent Modifiers
All schema builders support these fluent methods:
- `->description('Description text')`
- `->title('Title text')`
- `->default($value)`
- `->examples([$val1, $val2])`
- `->enum(['val1', 'val2'])`
- `->required()` (for object properties)

## Best Practices

### 1. Use Attributes for Simple Schemas
```php
public function handle(
    #[Schema(description: 'Search query')]
    string $query,

    #[Schema(description: 'Maximum results', minimum: 1, maximum: 100, default: 10)]
    int $limit = 10
): array {
    // Implementation
}
```

### 2. Use Builder API for Complex Schemas
```php
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

### 3. Always Provide Descriptions
Good descriptions help AI clients understand parameter purposes.

### 4. Use Appropriate Constraints
- String lengths, numeric ranges, enums reduce invalid inputs
- Required fields should be marked as such

### 5. Provide Sensible Defaults
Defaults make tools easier to use and reduce required parameters.

## Schema Validation

Schemas are validated by the MCP server. Invalid inputs will result in errors returned to the client. Always test your schemas with various inputs to ensure they work as expected.

## Examples

### Simple Tool with Attributes
```php
class EchoTool extends AbstractTool
{
    public function description(): string
    {
        return 'Echoes the input message back';
    }

    public function schema(): array
    {
        return []; // Using attributes instead
    }

    public function handle(
        #[Schema(description: 'Message to echo')]
        string $message
    ): array {
        return [['role' => 'user', 'content' => $message]];
    }
}
```

### Complex Tool with Builder
```php
class SearchTool extends AbstractTool
{
    public function description(): string
    {
        return 'Searches content with advanced filters';
    }

    public function schema(): array
    {
        $schema = new Schema();

        return [
            'query' => $schema->string(1, 500)->description('Search query'),
            'filters' => $schema->object([
                'category' => $schema->string()->description('Content category'),
                'date_from' => $schema->string()->format('date')->description('Start date'),
                'date_to' => $schema->string()->format('date')->description('End date'),
                'tags' => $schema->array($schema->string())->description('Tags to filter by'),
            ])->description('Search filters'),
            'limit' => $schema->integer(1, 100)->default(20)->description('Maximum results'),
        ];
    }

    public function handle(array $arguments): array
    {
        // Implementation
    }
}
```
