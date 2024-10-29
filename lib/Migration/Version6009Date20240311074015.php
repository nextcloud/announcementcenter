<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
}
