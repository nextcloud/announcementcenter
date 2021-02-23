<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
			$queryInsert->setParameter('aid', (int) $row['announcement_id'])
				->execute();
		}
		$output->finishProgress();

		$result->closeCursor();
	}
}
