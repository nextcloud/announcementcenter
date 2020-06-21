<?php

declare(strict_types=1);

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version3007Date20200621040843 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('announcements')) {
			$table = $schema->createTable('announcements');
			$table->addColumn('announcement_id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('announcement_time', 'integer', [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->addColumn('announcement_user', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('announcement_subject', 'string', [
				'notnull' => true,
				'length' => 512,
			]);
			$table->addColumn('announcement_message', 'text', [
				'notnull' => false,
			]);
			$table->addColumn('allow_comments', 'smallint', [
				'notnull' => false,
				'length' => 1,
				'default' => 1,
			]);
			$table->setPrimaryKey(['announcement_id']);
		}

		if (!$schema->hasTable('announcements_groups')) {
			$table = $schema->createTable('announcements_groups');
			$table->addColumn('announcement_id', 'integer', [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('gid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addUniqueIndex(['announcement_id', 'gid'], 'announce_group');
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
	}
}
