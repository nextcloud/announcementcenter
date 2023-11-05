<?php

declare(strict_types=1);

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version6010Date20231031000000 extends SimpleMigrationStep
{
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array   $options
	 *
	 * @return ISchemaWrapper|null
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper
	{
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('announcements_attach')) {
			$table = $schema->createTable('announcements_attach');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('announcement_id', 'bigint', [
				'notnull' => true,
				'length' => 8,
			]);
			$table->addColumn('type', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('data', 'string', [
				'notnull' => false,
			]);
			$table->addColumn('last_modified', 'bigint', [
				'notnull' => false,
				'length' => 8,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->addColumn('created_at', 'bigint', [
				'notnull' => false,
				'length' => 8,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->addColumn('created_by', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('deleted_at', 'bigint', [
				'notnull' => false,
				'length' => 8,
				'default' => 0,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}
}
