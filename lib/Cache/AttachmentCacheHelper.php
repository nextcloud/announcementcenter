<?php

/*
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);


namespace OCA\AnnouncementCenter\Cache;

use OCP\ICache;
use OCP\ICacheFactory;

class AttachmentCacheHelper
{
	/** @var ICache */
	private $cache;

	public function __construct(ICacheFactory $cacheFactory)
	{
		$this->cache = $cacheFactory->createDistributed('announcementcenter-attachments');
	}

	public function getAttachmentCount(int $announcementId): ?int
	{
		return $this->cache->get('count-' . $announcementId);
	}

	public function setAttachmentCount(int $announcementId, int $count): void
	{
		$this->cache->set('count-' . $announcementId, $count);
	}

	public function clearAttachmentCount(int $announcementId): void
	{
		$this->cache->remove('count-' . $announcementId);
	}
}
