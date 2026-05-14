<?php

use ExpressionEngine\Service\Migration\Migration;

class CreateMcpSettingsTable extends Migration
{
    private $table_name = 'mcp_settings';

    /**
     * Execute the migration
     *
     * @return void
     */
    public function up()
    {
        // Create the mcp_settings table
        ee()->dbforge->add_field([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'settings_key' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
                'comment' => 'Settings key (e.g., "mcp_settings")',
            ],
            'settings_value' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'JSON-encoded settings value',
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

        // Add unique index on settings_key
        ee()->dbforge->add_key('settings_key', true);

        // Create the table
        ee()->dbforge->create_table($this->table_name);

        // Add default timestamps
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
