<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Marvin Winkens <m.winkens@fz-juelich.de>
 *
 * @author Marvin Winkens <m.winkens@fz-juelich.de>
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

namespace OCA\AnnouncementCenter\Service;

use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class BannerManager {
	private string $appName;
	private AnnouncementMapper $mapper;
	private LoggerInterface $logger;
	private IConfig $config;
	private string $userReadKey = "readBannersList";
	public function __construct(string $appName, IConfig $config, AnnouncementMapper $mapper, LoggerInterface $logger) {
		$this->appName = $appName;
		$this->mapper = $mapper;
		$this->logger = $logger;
		$this->config = $config;
	}

	private function getReadBanners(string $uid): array {
		$readBanners = [];
		$readBannerValue = $this->config->getUserValue($uid, $this->appName, $this->userReadKey, null);
		if($readBannerValue) {
			$readBanners = explode(",", $readBannerValue);
		}
		return $readBanners;
	}

	private function setReadBanners(string $uid, array $readBanners): void {
		$readBannerValue = implode(",", $readBanners);
		$this->config->setUserValue($uid, $this->appName, $this->userReadKey, $readBannerValue);
	}

	/**
	 * Returns all unread banners of a user
	 * @param string $uid user id
	 * @return array of Announcements
	 */
	public function getUnreadBanners(string $uid): array {
		$readBanners = $this->getReadBanners($uid);
		/** TODO manage groups */
		return $this->mapper->getBanners($readBanners);
	}

	/**
	 * Sets a banner with id $id as read for a user with userId $uid
	 *
	 * @param string $uid user id
	 * @param string $id id of a banner
	 */
	public function markBannerRead(string $uid, string $id): void {
		$readBanners = $this->getReadBanners($uid);
		$readBanners[] = $id;

		/** maybe some banner annoucements got deleted, but don't spam the database */
		if(count($readBanners) > 5) {
			$existingReadBanners = $this->mapper->getAnnouncementsExisting($readBanners);
			$readBanners = array_map(function ($item) {
				return strval($item);
			}, $existingReadBanners);
		}
		$this->setReadBanners($uid, $readBanners);
	}
}
