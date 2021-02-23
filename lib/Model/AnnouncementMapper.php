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

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

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
	 * Deletes an entity from the table
	 * @param Entity $entity the entity that should be deleted
	 * @return Entity the deleted entity
	 * @since 14.0.0
	 */
	public function delete(Entity $entity): Entity {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->tableName)
			->where(
				$qb->expr()->eq('announcement_id', $qb->createNamedParameter($entity->getId()))
			);
		$qb->execute();
		return $entity;
	}

	/**
	 * @param array $userGroups
	 * @param int $offsetId
	 * @return Announcement[]
	 */
	public function getAnnouncements(array $userGroups, int $offsetId = 0): array {
		$query = $this->db->getQueryBuilder();

		$query->select('a.announcement_id')
			->from($this->getTableName(), 'a')
			->orderBy('a.announcement_time', 'DESC')
			->groupBy('a.announcement_id')
			->setMaxResults(7);

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
			$ids[] = (int) $row['announcement_id'];
		}
		$result->closeCursor();

		if (empty($ids)) {
			return [];
		}

		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('announcements')
			->orderBy('announcement_time', 'DESC')
			->where($query->expr()->in('announcement_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));

		return $this->findEntities($query);
	}
}
