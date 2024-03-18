<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marvin Winkens <m.winkens@fz-juelich.de>
 *
 * @author Marvin Winkens <m.winkens@fz-juelich.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add scheduling and scheduled deletion feature
 */
class Version6009Date20240311074015 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('announcements');

		if (!$table->hasColumn('announcement_schedule_time')) {
			$table->addColumn('announcement_schedule_time', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
				'length' => 4,
			]);
		}

		if (!$table->hasColumn('announcement_delete_time')) {
			$table->addColumn('announcement_delete_time', Types::INTEGER, [
				'notnull' => false,
				'default' => null,
				'length' => 4,
			]);
		}

		if (!$table->hasColumn('announcement_groups')) {
			$table->addColumn('announcement_groups', Types::STRING, [
				'notnull' => true,
				'default' => 'everyone',
			]);
		}

		if (!$table->hasColumn('announcement_not_types')) {
			$table->addColumn('announcement_not_types', Types::INTEGER, [
				'notnull' => true,
				'default' => 7, //all
				'length' => 4,
			]);
		}
		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
