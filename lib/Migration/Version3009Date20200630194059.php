<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
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
