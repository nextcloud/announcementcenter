<?php
/**
 * Nextcloud - Announcement Widget for Dashboard
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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

namespace OCA\AnnouncementCenter\Widgets\Service;


use OCA\AnnouncementCenter\Manager;
use OCP\IUser;
use OCP\IUserManager;

class AnnouncementService {

	/** @var IUserManager */
	private $userManager;

	/** @var Manager */
	private $announcementManager;


	/**
	 * AnnouncementService constructor.
	 *
	 * @param IUserManager $userManager
	 * @param Manager $announcementManager
	 */
	public function __construct(IUserManager $userManager, Manager $announcementManager) {
		$this->userManager = $userManager;
		$this->announcementManager = $announcementManager;
	}


	/**
	 * @return array
	 */
	public function getLastAnnouncement() {

		$rows = $this->announcementManager->getAnnouncements(1, 0);

		if (sizeof($rows) === 0) {
			return [];
		}

		\OC::$server->getLogger()->log(2, '>>> ' . json_encode($rows));
		$row = array_shift($rows);

		$displayName = $row['author'];
		$user = $this->userManager->get($displayName);
		if ($user instanceof IUser) {
			$displayName = $user->getDisplayName();
		}

		$row['author'] = $displayName;

		return $row;
	}


}