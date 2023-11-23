<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
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
				->setParameter('announcement_id', (int) $row['announcement_id'], IQueryBuilder::PARAM_INT)
				->setParameter('gid', $row['gid'])
			;

			$insert->execute();
		}
		$result->closeCursor();
	}
}
