<?php

namespace ExpressionEngine\Addons\Mcp\Support;

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * MCP Schema Helper
 *
 * Provides fluent methods for building JSON schemas for MCP tools, resources, and prompts.
 * Makes it easier to define input/output schemas without writing raw JSON schema arrays.
 *
 * Example usage:
 * $schema = new Schema();
 * return [
 *     'location' => $schema->string()->description('The location to get weather for')->required(),
 *     'units' => $schema->string()->enum(['celsius', 'fahrenheit'])->default('celsius'),
 * ];
 */
class Schema
{
    /**
     * Create a string schema
     */
    public function string(?int $minLength = null, ?int $maxLength = null, ?string $pattern = null, ?string $format = null): SchemaBuilder
    {
        return new SchemaBuilder('string', [
            'minLength' => $minLength,
            'maxLength' => $maxLength,
            'pattern' => $pattern,
            'format' => $format,
        ]);
    }

    /**
     * Create an integer schema
     */
    public function integer(?int $minimum = null, ?int $maximum = null): SchemaBuilder
    {
        return new SchemaBuilder('integer', [
            'minimum' => $minimum,
            'maximum' => $maximum,
        ]);
    }

    /**
     * Create a number schema
     */
    public function number(?float $minimum = null, ?float $maximum = null): SchemaBuilder
    {
        return new SchemaBuilder('number', [
            'minimum' => $minimum,
            'maximum' => $maximum,
        ]);
    }

    /**
     * Create a boolean schema
     */
    public function boolean(): SchemaBuilder
    {
        return new SchemaBuilder('boolean');
    }

    /**
     * Create an array schema
     */
    public function array(?array $items = null, ?int $minItems = null, ?int $maxItems = null): SchemaBuilder
    {
        return new SchemaBuilder('array', [
            'items' => $items,
            'minItems' => $minItems,
            'maxItems' => $maxItems,
        ]);
    }

    /**
     * Create an object schema
     */
    public function object(?array $properties = null, ?array $required = null): SchemaBuilder
    {
        return new SchemaBuilder('object', [
            'properties' => $properties,
            'required' => $required,
        ]);
    }

    /**
     * Create an enum schema
     */
    public function enum(array $values): SchemaBuilder
    {
        return new SchemaBuilder('string', [
            'enum' => $values,
        ]);
    }

    /**
     * Create a oneOf schema (union type)
     */
    public function oneOf(array $schemas): SchemaBuilder
    {
        return new SchemaBuilder(null, [
            'oneOf' => $schemas,
        ]);
    }

    /**
     * Create a const schema (literal value)
     *
     * @param  mixed  $value
     */
    public function const($value): SchemaBuilder
    {
        return new SchemaBuilder(null, [
            'const' => $value,
        ]);
    }
}

/**
 * Schema Builder for fluent API
 */
class SchemaBuilder
{
    private array $schema;

    public function __construct(?string $type = null, array $initial = [])
    {
        $this->schema = $initial;
        if ($type) {
            $this->schema['type'] = $type;
        }
    }

    /**
     * Set description
     */
    public function description(string $description): self
    {
        $this->schema['description'] = $description;

        return $this;
    }

    /**
     * Set title
     */
    public function title(string $title): self
    {
        $this->schema['title'] = $title;

        return $this;
    }

    /**
     * Set default value
     *
     * @param  mixed  $default
     */
    public function default($default): self
    {
        $this->schema['default'] = $default;

        return $this;
    }

    /**
     * Set examples
     */
    public function examples(array $examples): self
    {
        $this->schema['examples'] = $examples;

        return $this;
    }

    /**
     * Set enum values
     */
    public function enum(array $values): self
    {
        $this->schema['enum'] = $values;

        return $this;
    }

    /**
     * Mark as required (for object properties)
     */
    public function required(): self
    {
        // This is handled at the object level, not individual property level
        // Return self for chaining
        return $this;
    }

    /**
     * Set minimum value (for numbers)
     */
    public function min(float $min): self
    {
        $this->schema['minimum'] = $min;

        return $this;
    }

    /**
     * Set maximum value (for numbers)
     */
    public function max(float $max): self
    {
        $this->schema['maximum'] = $max;

        return $this;
    }

    /**
     * Set minimum length (for strings)
     */
    public function minLength(int $length): self
    {
        $this->schema['minLength'] = $length;

        return $this;
    }

    /**
     * Set maximum length (for strings)
     */
    public function maxLength(int $length): self
    {
        $this->schema['maxLength'] = $length;

        return $this;
    }

    /**
     * Set pattern (for strings)
     */
    public function pattern(string $pattern): self
    {
        $this->schema['pattern'] = $pattern;

        return $this;
    }

    /**
     * Set format (for strings)
     */
    public function format(string $format): self
    {
        $this->schema['format'] = $format;

        return $this;
    }

    /**
     * Set custom property
     *
     * @param  mixed  $value
     */
    public function set(string $key, $value): self
    {
        $this->schema[$key] = $value;

        return $this;
    }

    /**
     * Get the schema array
     */
    public function toArray(): array
    {
        // Filter out null values as JSON Schema doesn't allow null for properties like format, pattern, etc.
        return array_filter($this->schema, function ($value) {
            return $value !== null;
        });
    }

    /**
     * Convert to array when used in array context
     */
    public function __toArray(): array
    {
        return $this->toArray();
    }
}
