<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Model;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Announcement>
 */
class AnnouncementMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'announcements', Announcement::class);
	}

	/**
	 * @param int $id
	 * @return Announcement
	 * @throws DoesNotExistException
	 */
	public function getById(int $id): Announcement {
		$query = $this->db->getQueryBuilder();

		$query->select('*')
			->from($this->getTableName())
			->where(
				$query->expr()->eq('announcement_id', $query->createNamedParameter($id))
			);

		return $this->findEntity($query);
	}

	/**
	 * @param int $id
	 * @return int the number of affected rows
	 * @throws DoesNotExistException
	 */
	public function resetScheduleTimeById(int $id) {
		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('announcement_time', 'announcement_schedule_time')
			->where($query->expr()->eq('announcement_id', $query->createNamedParameter($id)));
		$query->executeStatement();

		$query = $this->db->getQueryBuilder();
		$query->update($this->getTableName())
			->set('announcement_schedule_time', $query->expr()->literal(0, IQueryBuilder::PARAM_INT))
			->where($query->expr()->eq('announcement_id', $query->createNamedParameter($id)));
		return $query->executeStatement();
	}

	/**
	 * Deletes an entity from the table
	 * @param Entity $entity the entity that should be deleted
	 * @return Entity the deleted entity
	 * @psalm-return Announcement the deleted entity
	 * @since 14.0.0
	 */
	public function delete(Entity $entity): Entity {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('announcement_id', $qb->createNamedParameter($entity->getId()))
			);
		$qb->execute();
		return $entity;
	}

	/**
	 * @param array $userGroups
	 * @param int $offsetId
	 * @param int $limit
	 * @return Announcement[]
	 */
	public function getAnnouncements(array $userGroups, int $offsetId = 0, int $limit = 7): array {
		$query = $this->db->getQueryBuilder();

		$query->select('a.announcement_id')
			->from($this->getTableName(), 'a')
			->orderBy('a.announcement_time', 'DESC')
			->groupBy('a.announcement_id')
			->setMaxResults($limit);

		if (!empty($userGroups)) {
			$query->leftJoin('a', 'announcements_map', 'ag', $query->expr()->eq(
				'a.announcement_id', 'ag.announcement_id'
			))
				->andWhere($query->expr()->in('ag.gid', $query->createNamedParameter($userGroups, IQueryBuilder::PARAM_STR_ARRAY)));
		}

		if ($offsetId > 0) {
			$query->andWhere($query->expr()->lt('a.announcement_id', $query->createNamedParameter($offsetId, IQueryBuilder::PARAM_INT)));
		}

		$ids = [];
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$ids[] = (int)$row['announcement_id'];
		}
		$result->closeCursor();

		if (empty($ids)) {
			return [];
		}

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->orderBy('announcement_time', 'DESC')
			->where($query->expr()->in('announcement_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));

		return $this->findEntities($query);
	}

	/**
	 * Get all announcements, that have a schedule time
	 * @return Announcement[]
	 */
	public function getAnnouncementsScheduled() : array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->orderBy('announcement_schedule_time', 'ASC')  // respect order
			->where($query->expr()->isNotNull('announcement_schedule_time'))
			->andWhere($query->expr()->gt(
				'announcement_schedule_time',
				$query->expr()->literal(0, IQueryBuilder::PARAM_INT)
			));
		return $this->findEntities($query);
	}

	/**
	 * Get all announcements, that have a deletion time
	 * @return Announcement[]
	 */
	public function getAnnouncementsScheduledDelete() : array {
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->orderBy('announcement_delete_time', 'ASC')  // highest chance to be deleted
			->where($query->expr()->isNotNull('announcement_delete_time'))
			->andWhere($query->expr()->gt(
				'announcement_delete_time',
				$query->expr()->literal(0, IQueryBuilder::PARAM_INT)
			));
		return $this->findEntities($query);
	}
}
