<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateComponentSettingsTable extends Migration
{
    private $table_name = 'mcp_component_settings';

    /**
     * Execute the migration
     *
     * @return void
     */
    public function up()
    {
        // Create the mcp_component_settings table
        ee()->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'addon_name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'The addon short name',
            ],
            'component_type' => [
                'type' => 'ENUM',
                'constraint' => '"tool","resource","prompt"',
                'null' => false,
                'comment' => 'Type of MCP component',
            ],
            'component_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
                'comment' => 'Name of the component (from attribute or generated)',
            ],
            'enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'unsigned' => true,
                'default' => 1,
                'null' => false,
                'comment' => 'Whether this component is enabled (1 = enabled, 0 = disabled)',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        // Add primary key
        ee()->dbforge->add_key('id', true);

        // Add unique index on (addon_name, component_type, component_name)
        ee()->dbforge->add_key(['addon_name', 'component_type', 'component_name'], true);

        // Add indexes for common queries
        ee()->dbforge->add_key('addon_name');
        ee()->dbforge->add_key('component_type');
        ee()->dbforge->add_key('enabled');

        // Create the table
        ee()->dbforge->create_table($this->table_name);

        // Add default timestamps using raw SQL (MySQL doesn't support CURRENT_TIMESTAMP in DEFAULT for DATETIME in all versions)
        $tableName = ee()->db->dbprefix.$this->table_name;
        ee()->db->query("ALTER TABLE `{$tableName}` MODIFY `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP");
        ee()->db->query("ALTER TABLE `{$tableName}` MODIFY `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

        // Log success
        if (function_exists('ee') && isset(ee()->logger)) {
            ee()->logger->developer("[MCP Migration] Created {$this->table_name} table");
        }
    }

    /**
     * Rollback the migration
     *
     * @return void
     */
    public function down()
    {
        // Drop the table
        ee()->dbforge->drop_table($this->table_name);

        // Log success
        if (function_exists('ee') && isset(ee()->logger)) {
            ee()->logger->developer("[MCP Migration] Dropped {$this->table_name} table");
        }
    }
}
