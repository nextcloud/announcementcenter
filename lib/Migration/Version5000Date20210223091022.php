<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Move to a groups table with PK for cluster support
 */
class Version5000Date20210223091022 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('announcements_map')) {
			$table = $schema->createTable('announcements_map');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('announcement_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('gid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['announcement_id', 'gid'], 'announce_gid_map');
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$insert = $this->connection->getQueryBuilder();
		$insert->insert('announcements_map')
			->values([
				'announcement_id' => $insert->createParameter('announcement_id'),
				'gid' => $insert->createParameter('gid'),
			]);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('announcements_groups');


		$result = $query->execute();
		while ($row = $result->fetch()) {
			$insert
				->setParameter('announcement_id', (int)$row['announcement_id'], IQueryBuilder::PARAM_INT)
				->setParameter('gid', $row['gid'])
			;

			$insert->execute();
		}
		$result->closeCursor();
	}
}
