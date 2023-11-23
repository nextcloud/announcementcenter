<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3009Date20200630194059 extends SimpleMigrationStep {
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
			$table->addColumn('announcement_id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('announcement_time', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->addColumn('announcement_user', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('announcement_subject', Types::STRING, [
				'notnull' => true,
				'length' => 512,
			]);
			$table->addColumn('announcement_message', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('allow_comments', Types::SMALLINT, [
				'notnull' => false,
				'length' => 1,
				'default' => 1,
			]);
			$table->setPrimaryKey(['announcement_id']);
		}

		if (!$schema->hasTable('announcements_groups')) {
			$table = $schema->createTable('announcements_groups');
			$table->addColumn('announcement_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('gid', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addUniqueIndex(['announcement_id', 'gid'], 'announce_group');
		}
		return $schema;
	}
}
