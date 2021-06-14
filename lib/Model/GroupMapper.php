<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Joas Schilling <coding@schilljs.com>
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

namespace OCA\AnnouncementCenter\Model;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class GroupMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'announcements_map', Group::class);
	}

	/**
	 * @param Announcement $announcement
	 * @return string[]
	 */
	public function getGroupsForAnnouncement(Announcement $announcement): array {
		$result = $this->getGroupsForAnnouncements([$announcement]);
		return $result[$announcement->getId()] ?? [];
	}

	/**
	 * @param Announcement $announcement
	 */
	public function deleteGroupsForAnnouncement(Announcement $announcement): void {
		$query = $this->db->getQueryBuilder();
		$query->delete('announcements_map')
			->where($query->expr()->eq('announcement_id', $query->createNamedParameter($announcement->getId())));
		$query->execute();
	}

	/**
	 * @param Announcement[] $announcements
	 * @return array
	 */
	public function getGroupsForAnnouncements(array $announcements): array {
		$ids = array_map(function (Announcement $announcement) {
			return $announcement->getId();
		}, $announcements);

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('announcements_map')
			->where($query->expr()->in('announcement_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));

		/** @var Group[] $results */
		$results = $this->findEntities($query);

		$groups = [];
		foreach ($results as $result) {
			if (!isset($groups[$result->getId()])) {
				$groups[$result->getId()] = [];
			}
			$groups[$result->getId()][] = $result->getGroup();
		}

		return $groups;
	}
}
