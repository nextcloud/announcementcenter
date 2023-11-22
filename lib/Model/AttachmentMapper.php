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
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use OCP\Share\IManager;
use OCP\Share\IShare;

/** @template-extends QBMapper<Attachment> */
class AttachmentMapper extends QBMapper
{
	private AnnouncementMapper $announcementMapper;
	private IUserManager $userManager;
	private LoggerInterface $logger;
	private GroupMapper $groupMapper;
	private IManager $shareManager;
	private ?string $userId;
	private IRootFolder $rootFolder;
	/**
	 * AttachmentMapper constructor.
	 *
	 * @param IDBConnection $db
	 * @param AnnouncementMapper $announcementMapper
	 * @param IUserManager $userManager
	 */
	public function __construct(IDBConnection $db, AnnouncementMapper $announcementMapper, IUserManager $userManager, IManager $shareManager, GroupMapper $groupMapper, ?string $userId, IRootFolder $rootFolder, LoggerInterface $logger)
	{
		parent::__construct($db, 'announcements_attach', Attachment::class);
		$this->announcementMapper = $announcementMapper;
		$this->userManager = $userManager;
		$this->logger = $logger;
		$this->groupMapper = $groupMapper;
		$this->shareManager = $shareManager;
		$this->userId = $userId;
		$this->rootFolder = $rootFolder;
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
	public function deleteAttachmentsSharesForAnnouncement(Announcement $announcement): void
	{;
		$attachments = $this->findAll($announcement->getId());
		$groups = $this->groupMapper->getGroupsByAnnouncementId($announcement->getId());
		foreach ($attachments as $attachment) {
			$fileId = $attachment->getFileId();
			$files = $this->rootFolder->getUserFolder($this->userId)->getById($fileId);
			foreach ($files as $file) {
				// 获取文件的所有分享
				$shares = $this->shareManager->getSharesBy($this->userId, IShare::TYPE_GROUP, $file);

				foreach ($shares as $share) {
					// 检查分享是否属于我们关心的组
					if (in_array($share->getSharedWith(), $groups)) {
						$this->logger->warning("del:" . $share->getSharedWith());
						// 如果是，则删除分享
						$this->shareManager->deleteShare($share);
					}
				}
			}
		}

		// 删除附件信息
		$query = $this->db->getQueryBuilder();
		$query->delete('announcements_attach')
			->where($query->expr()->eq('announcement_id', $query->createNamedParameter($announcement->getId())));
		$query->execute();
	}

	/**
	 * @param $announcementId
	 * @return Entity[]
	 * @throws Exception
	 */
	public function findAll($announcementId): array
	{
		$this->logger->warning("render");
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
		} catch (DoesNotExistException | MultipleObjectsReturnedException $e) {
		}
		return false;
	}
}
