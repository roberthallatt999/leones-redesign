<?php

use ExpressionEngine\Service\Migration\Migration;

class AddVisibilityModeToComponentSettings extends Migration
{
    private $table_name = 'mcp_component_settings';

    /**
     * Execute the migration
     *
     * @return void
     */
    public function up()
    {
        $tableName = ee()->db->dbprefix.$this->table_name;

        // Add visibility_mode column
        // Values: 'hidden' (default, current behavior) or 'visible_disabled' (shows but returns error)
        ee()->dbforge->add_column($this->table_name, [
            'visibility_mode' => [
                'type' => 'ENUM',
                'constraint' => '"hidden","visible_disabled"',
                'default' => 'hidden',
                'null' => false,
                'comment' => 'Visibility mode: hidden (default) or visible_disabled (shows but returns error)',
            ],
        ]);

        // Log success
        if (function_exists('ee') && isset(ee()->logger)) {
            ee()->logger->developer("[MCP Migration] Added visibility_mode column to {$this->table_name} table");
        }
    }

    /**
     * Rollback the migration
     *
     * @return void
     */
    public function down()
    {
        // Drop the column
        ee()->dbforge->drop_column($this->table_name, 'visibility_mode');

        // Log success
        if (function_exists('ee') && isset(ee()->logger)) {
            ee()->logger->developer("[MCP Migration] Removed visibility_mode column from {$this->table_name} table");
        }
    }
}
