<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Remove previous default config and user preference of activity setting
 * They are no long editable since we have direct emails, but they still send
 * otherwise.
 */
class Version6006Date20230516000000 extends SimpleMigrationStep {
	protected IDBConnection $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		// Remove former activity default setting
		$query = $this->connection->getQueryBuilder();
		$query->delete('appconfig')
			->where($query->expr()->eq('appid', $query->createNamedParameter('activity')))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('notify_email_announcementcenter')));
		$query->executeStatement();

		// Remove former activity user preference
		$query = $this->connection->getQueryBuilder();
		$query->delete('preferences')
			->where($query->expr()->eq('appid', $query->createNamedParameter('activity')))
			->andWhere($query->expr()->eq('configkey', $query->createNamedParameter('notify_email_announcementcenter')));
		$query->executeStatement();
	}
}
