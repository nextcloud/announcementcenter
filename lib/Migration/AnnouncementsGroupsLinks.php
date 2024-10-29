<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


namespace OCA\AnnouncementCenter\Migration;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AnnouncementsGroupsLinks implements IRepairStep {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName(): string {
		return 'Add read permissions for existing announcements';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @since 9.1.0
	 * @param IOutput $output
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output) {
		$queryInsert = $this->connection->getQueryBuilder();
		$queryInsert->insert('announcements_map')
			->values([
				'announcement_id' => $queryInsert->createParameter('aid'),
				'gid' => $queryInsert->createNamedParameter('everyone'),
			]);

		$query = $this->connection->getQueryBuilder();
		$query->select(['a.announcement_id', 'a.announcement_subject'])
			->from('announcements', 'a')
			->leftJoin('a', 'announcements_map', 'ag', $query->expr()->eq(
				'a.announcement_id', 'ag.announcement_id'
			))
			->where($query->expr()->isNull('ag.gid'));
		$result = $query->execute();

		$output->startProgress();
		while ($row = $result->fetch()) {
			$output->advance(1, $row['announcement_subject']);
			$queryInsert->setParameter('aid', (int)$row['announcement_id'])
				->execute();
		}
		$output->finishProgress();

		$result->closeCursor();
	}
}
