<?php

/**
 * @author TJ Draper <tj@buzzingpixel.com>
 * @copyright 2017 BuzzingPixel, LLC
 * @license https://buzzingpixel.com/software/ansel-ee/license
 * @link https://buzzingpixel.com/software/ansel-ee
 */

namespace BuzzingPixel\Ansel\Service\Install;

use EllisLab\ExpressionEngine\Service\Database\Query as QueryBuilder;
use CI_DB_mysqli_forge as DBForge;
use EllisLab\ExpressionEngine\Service\Model\Facade as RecordBuilder;
use EllisLab\ExpressionEngine\Service\Model\Query\Builder as RecordQueryBuilder;

/**
 * Class RecordInstallerService
 *
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class RecordInstallerService
{
	/**
	 * Create table (for testing)
	 *
	 * @var bool $createTable
	 */
	public $createTable = false;

	/**
	 * Delete table (for testing)
	 *
	 * @var bool $deleteTable
	 */
	public $deleteTable = false;

	/**
	 * Insert field (for testing)
	 *
	 * @var bool $insertField
	 */
	public $insertField = false;

	/**
	 * @var string $namespace
	 */
	private $namespace = '';

	/**
	 * @var array $models
	 */
	private $models = array();

	/**
	 * @var \CI_DB_driver $queryBuilder
	 */
	private $queryBuilder;

	/**
	 * @var DBForge $dbForge
	 */
	private $dbForge;

	/**
	 * @var RecordBuilder $recordBuilder
	 */
	private $recordBuilder;

	/**
	 * Constructor
	 *
	 * @param string $namespace
	 * @param array $models Keys = class name, values = namespace
	 * @param QueryBuilder $queryBuilder
	 * @param DBForge $dbForge
	 * @param RecordBuilder $recordBuilder
	 */
	public function __construct(
		$namespace,
		$models,
		QueryBuilder $queryBuilder,
		DBForge $dbForge,
		RecordBuilder $recordBuilder
	) {
		$this->namespace = $namespace;
		$this->models = $models;
		$this->queryBuilder = $queryBuilder;
		$this->dbForge = $dbForge;
		$this->recordBuilder = $recordBuilder;
	}

	/**
	 * Install/Update records
	 */
	public function installUpdate()
	{
		// Loop through models and send to the private installer method
		foreach ($this->models as $modelClassName => $modelClass) {
			$this->processInstallUpdate($modelClassName, $modelClass);
		}
	}

	/**
	 * Remove records
	 */
	public function remove()
	{
		// Loop through models and send to the private remove method
		foreach ($this->models as $modelClass) {
			$this->processRemove($modelClass);
		}
	}

	/**
	 * Process model install/update
	 *
	 * @param string $modelClassName
	 * @param string $modelClass
	 */
	private function processInstallUpdate($modelClassName, $modelClass)
	{
		// Build the full model class
		$fullModelClass = "\\{$this->namespace}\\{$modelClass}";

		// Get model reflection
		$reflection = new \ReflectionClass($fullModelClass);

		// Get the table name Property
		$tableNameProp = $reflection->getProperty('_table_name');

		// Make table name property accessible
		$tableNameProp->setAccessible(true);

		// Get the table name
		$tableName = $tableNameProp->getValue();

		// Get the primary key property
		$primaryKeyProp = $reflection->getProperty('_primary_key');

		// Make the primary key property accessible
		$primaryKeyProp->setAccessible(true);

		// Get the primary key
		$primaryKey = $primaryKeyProp->getValue();

		// Start the field array with the primary key
		$fieldArray = array(
			$primaryKey => array(
				'type' => 'INT',
				'unsigned' => true,
				'auto_increment' => true
			)
		);

		// Get the db columns property
		$dbColumnsProp = $reflection->getProperty('_db_columns');

		// Make the db columns property accessible
		$dbColumnsProp->setAccessible(true);

		// Get the db columns
		$dbColumns = $dbColumnsProp->getValue();

		// Merge arrays to make final fields set
		$fieldArray = array_merge($fieldArray, $dbColumns);


		/**
		 * Check if the table exists, make sure it is up to date if so
		 * Otherwise add the table
		 */

		if ($this->queryBuilder->table_exists($tableName)) {
			// Iterate over DB columns
			foreach ($dbColumns as $key => $col) {
				// Check if the column exists
				if ($this->queryBuilder->field_exists($key, $tableName)) {
					continue;
				}

				// Add the column
				$this->dbForge->add_column($tableName, array(
					$key => $col
				));

				// Set create table class property for testing
				$this->insertField = true;
			}
		} else {
			/**
			 * Insert the table
			 */

			// Add fields to forge
			$this->dbForge->add_field($fieldArray);

			// Set the primary key
			$this->dbForge->add_key($primaryKey, true);

			// Create the table
			$this->dbForge->create_table($tableName, true);

			// Set create table class property for testing
			$this->createTable = true;
		}


		/**
		 * Check for rows property
		 */

		if (! property_exists($fullModelClass, '_rows')) {
			return;
		}

		// Get the rows key property
		$rowsKeyProp = $reflection->getProperty('_rows_key');

		// Make the db columns property accessible
		$rowsKeyProp->setAccessible(true);

		// Get the db columns
		$rowsKey = $rowsKeyProp->getValue();

		// Get the rows property
		$rowsProp = $reflection->getProperty('_rows');

		// Make the db columns property accessible
		$rowsProp->setAccessible(true);

		// Get the db columns
		$rows = $rowsProp->getValue();

		// Iterate over rows and make sure they exist
		foreach ($rows as $row) {
			// Check for the existing row
			/** @var RecordQueryBuilder $record */
			$record = $this->recordBuilder->get("ansel:{$modelClassName}");
			$record->filter($rowsKey, $row[$rowsKey]);
			$record = $record->first();

			// Now check for the record's existence
			if ($record) {
				continue;
			}

			// We need to make a record now
			/** @var \EllisLab\ExpressionEngine\Service\Model\Model $record */
			$record = $this->recordBuilder->make("ansel:{$modelClassName}");

			// Iterate over values and add to record
			foreach ($row as $key => $val) {
				$record->setProperty($key, $val);
			}

			// Save the record to the database
			$record->save();
		}
	}

	/**
	 * Process model removal
	 *
	 * @param string $modelClass
	 */
	private function processRemove($modelClass)
	{
		// Build the full model class
		$fullModelClass = "\\{$this->namespace}\\{$modelClass}";

		// Get model reflection
		$reflection = new \ReflectionClass($fullModelClass);

		// Get the table name Property
		$tableNameProp = $reflection->getProperty('_table_name');

		// Make table name property accessible
		$tableNameProp->setAccessible(true);

		// Get the table name
		$tableName = $tableNameProp->getValue();

		// Check if the table exists
		if ($this->queryBuilder->table_exists($tableName)) {
			// Drop the table
			$this->dbForge->drop_table($tableName);

			// Set create table class property for testing
			$this->deleteTable = true;
		}
	}
}
