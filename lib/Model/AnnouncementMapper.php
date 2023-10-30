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
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * @template-extends QBMapper<Announcement>
 */
class AnnouncementMapper extends QBMapper
{
	protected LoggerInterface $logger;
	public function __construct(IDBConnection $db, LoggerInterface $logger)
	{
		parent::__construct($db, 'announcements', Announcement::class);
		$this->logger = $logger;
	}

	/**
	 * @param int $id
	 * @return Announcement
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function getById(int $id): Announcement
	{
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
	 * @psalm-return Announcement the deleted entity
	 * @since 14.0.0
	 */
	public function delete(Entity $entity): Entity
	{
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where(
				$qb->expr()->eq('announcement_id', $qb->createNamedParameter($entity->getId()))
			);
		$qb->execute();
		return $entity;
	}
	public function searchAnnouncements(string $userId, array $userGroups, string $filterKey, int $page = 1, int $pageSize = 10)
	{
		$query = $this->db->getQueryBuilder();
		$query->select('a.announcement_id')
			->from($this->getTableName(), 'a')
			->orderBy('a.announcement_time', 'DESC')
			->groupBy('a.announcement_id');

		if (!empty($userGroups)) {
			$query->leftJoin('a', 'announcements_map', 'ag', $query->expr()->eq(
				'a.announcement_id',
				'ag.announcement_id'
			))->andWhere(
				$query->expr()->orX(
					$query->expr()->in('ag.gid', $query->createNamedParameter($userGroups, IQueryBuilder::PARAM_STR_ARRAY)),
					$query->expr()->eq('a.announcement_user', $query->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
				)
			);
		}
		$all_ids = [];
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$all_ids[] = (int) $row['announcement_id'];
		}
		$result->closeCursor();
		if (empty($all_ids)) {
			return [
				'data' => [],
				'total' => 0,
				'pages' => 0
			];
		}
		$ids = array_slice($all_ids, ($page - 1) * $pageSize, $pageSize);
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->orderBy('announcement_time', 'DESC')
			->where(
				$qb->expr()->andX($qb->expr()->orX(
					$qb->expr()->like('announcement_user', $qb->expr()->literal('%' . $filterKey . '%')),
					$qb->expr()->like('announcement_subject', $qb->expr()->literal('%' . $filterKey . '%')),
					$qb->expr()->like('announcement_message', $qb->expr()->literal('%' . $filterKey . '%'))
				), $qb->expr()->in('announcement_id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)))
			);
		$results = $this->findEntities($qb);
		$this->logger->warning('results:' . json_encode($results));
		return [
			'data' => $results,
			'total' => count($all_ids),
			'pages' => ceil(count($all_ids) / $pageSize)
		];
	}


	public function getAnnouncements(string $userId, array $userGroups, int $page = 1, int $pageSize = 10): array
	{
		$query = $this->db->getQueryBuilder();
		$query->select('a.announcement_id')
			->from($this->getTableName(), 'a')
			->orderBy('a.announcement_time', 'DESC')
			->groupBy('a.announcement_id');

		if (!empty($userGroups)) {
			$query->leftJoin('a', 'announcements_map', 'ag', $query->expr()->eq(
				'a.announcement_id',
				'ag.announcement_id'
			))
				->andWhere(
					$query->expr()->orX(
						$query->expr()->in('ag.gid', $query->createNamedParameter($userGroups, IQueryBuilder::PARAM_STR_ARRAY)),
						$query->expr()->eq('a.announcement_user', $query->createNamedParameter($userId, IQueryBuilder::PARAM_STR))
					)
				);
		}

		$all_ids = [];
		$result = $query->execute();
		while ($row = $result->fetch()) {
			$all_ids[] = (int) $row['announcement_id'];
		}

		$result->closeCursor();

		if (empty($all_ids)) {
			return [
				'data' => [],
				'total' => 0,
				'pages' => 0
			];
		}
		$ids = array_slice($all_ids, ($page - 1) * $pageSize, $pageSize);
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from($this->getTableName())
			->orderBy('announcement_time', 'DESC')
			->where($query->expr()->in('announcement_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));
		$results = $this->findEntities($query);
		// $this->logger->warning('count1:' . $results[0]);
		return [
			'data' => $results,
			'total' => count($all_ids),
			'pages' => ceil(count($all_ids) / $pageSize),
		];
	}


	/**
	 * @throws Exception
	 */
	public function updateAnnouncement(Announcement $entity): Announcement
	{
		// if entity wasn't changed it makes no sense to run a db query
		$properties = $entity->getUpdatedFields();
		if (\count($properties) === 0) {
			return $entity;
		}

		// entity needs an id
		$id = $entity->getId();
		if ($id === null) {
			throw new \InvalidArgumentException(
				'Entity which should be updated has no id'
			);
		}

		// get updated fields to save, fields have to be set using a setter to
		// be saved
		// do not update the id field
		unset($properties['id']);

		$qb = $this->db->getQueryBuilder();
		$qb->update($this->tableName);

		// build the fields
		foreach ($properties as $property => $updated) {
			$column = $entity->propertyToColumn($property);
			$getter = 'get' . ucfirst($property);
			$value = $entity->$getter();

			$type = $this->getParameterTypeForProperty($entity, $property);
			$qb->set($column, $qb->createNamedParameter($value, $type));
		}

		$idType = $this->getParameterTypeForProperty($entity, 'id');

		$qb->where(
			$qb->expr()->eq('announcement_id', $qb->createNamedParameter($id, $idType))
		);
		$qb->executeStatement();

		return $entity;
	}
}
