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

use OCA\AnnouncementCenter\Model\Attachment;
use OCA\AnnouncementCenter\Model\Share;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<Share>
 */
class ShareMapper extends QBMapper
{
	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, 'attachments_share', Share::class);
	}

	/**
	 * @param Attachment $attachment
	 * @return string[]
	 */
	public function getSharesForAttachment(Attachment $attachment): array
	{
		$result = $this->getSharesForAttachments([$attachment]);
		return $result[$attachment->getId()] ?? [];
	}
	/**
	 * @param int $id
	 * @return string[]
	 */
	public function getSharesByAttachmentId(int $id): array
	{
		$result = $this->getSharesByAttachmentIds([$id]);
		return $result[$id] ?? [];
	}
	/**
	 * @param Attachment $attachment
	 */
	public function deleteSharesForAttachment(Attachment $attachment): void
	{
		$query = $this->db->getQueryBuilder();
		$query->delete('attachments_share')
			->where($query->expr()->eq('attachment_id', $query->createNamedParameter($attachment->getId())));
		$query->execute();
	}
	/**
	 * @param int[] $ids
	 * @return array
	 */
	public function getSharesByAttachmentIds(array $ids): array
	{
		$query = $this->db->getQueryBuilder();
		$query->select('*')
			->from('attachments_share')
			->where($query->expr()->in('attachment_id', $query->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)));

		/** @var Share[] $results */
		$results = $this->findEntities($query);

		$Shares = [];
		foreach ($results as $result) {
			if (!isset($Shares[$result->getId()])) {
				$Shares[$result->getId()] = [];
			}
			$Shares[$result->getId()][] = $result->getShare();
		}

		return $Shares;
	}
	/**
	 * @param Attachment[] $Attachments
	 * @return array
	 */
	public function getSharesForAttachments(array $attachments): array
	{
		$ids = array_map(function (Attachment $attachment) {
			return $attachment->getId();
		}, $attachments);
		return $this->getSharesByAttachmentIds($ids);
	}
}
