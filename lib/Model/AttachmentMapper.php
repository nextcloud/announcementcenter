<?php

/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\AnnouncementCenter\Model;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IUserManager;

/** @template-extends BaseMapper<Attachment> */
class AttachmentMapper extends BaseMapper
{
	private AnnouncementMapper $announcementMapper;
	private IUserManager $userManager;


	/**
	 * AttachmentMapper constructor.
	 *
	 * @param IDBConnection $db
	 * @param AnnouncementMapper $announcementMapper
	 * @param IUserManager $userManager
	 */
	public function __construct(IDBConnection $db, AnnouncementMapper $announcementMapper, IUserManager $userManager)
	{
		parent::__construct($db, 'announcements_attach', Attachment::class);
		$this->announcementMapper = $announcementMapper;
		$this->userManager = $userManager;
		
	}

	/**
	 * @param int $id
	 * @return Attachment
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function find($id): Attachment
    {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @param int $announcementId
	 * @param string $data
	 * @return Attachment
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function findByData(int $announcementId, string $data): Attachment
    {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('announcement_id', $qb->createNamedParameter($announcementId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('data', $qb->createNamedParameter($data, IQueryBuilder::PARAM_STR)));

		return $this->findEntity($qb);
	}

	/**
	 * @param $announcementId
	 * @return Entity[]
	 * @throws Exception
	 */
	public function findAll($announcementId): array
    {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('announcement_id', $qb->createNamedParameter($announcementId, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->eq('deleted_at', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));


		return $this->findEntities($qb);
	}

    /**
     * @param int|null $announcementId
     * @param bool $withOffset
     * @return array
     * @throws Exception
     */
	public function findToDelete(int $announcementId = null, bool $withOffset = true): array
    {
		// add buffer of 5 min
		$timeLimit = time() - (60 * 5);
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->gt('deleted_at', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)));
		if ($withOffset) {
			$qb
				->andWhere($qb->expr()->lt('deleted_at', $qb->createNamedParameter($timeLimit, IQueryBuilder::PARAM_INT)));
		}
		if ($announcementId !== null) {
			$qb
				->andWhere($qb->expr()->eq('announcement_id', $qb->createNamedParameter($announcementId, IQueryBuilder::PARAM_INT)));
		}

		return $this->findEntities($qb);
	}


    /**
     * Check if $userId is owner of Entity with $id
     *
     * @param $userId string userId
     * @param $id int|string unique entity identifier
     * @return boolean
     * @throws Exception
     */
	public function isOwner(string $userId, int|string $id): bool
	{
		try {
			$attachment = $this->find($id);
			return $this->announcementMapper->isOwner($userId, $attachment->getAnnouncementId());
		} catch (DoesNotExistException|MultipleObjectsReturnedException $e) {
		}
        return false;
	}
}
