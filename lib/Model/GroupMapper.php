<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Model;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Group>
 */
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
