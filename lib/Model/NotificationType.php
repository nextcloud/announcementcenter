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

namespace OCA\AnnouncementCenter\Model;

class NotificationType {
	protected $notificationTypes;

	public function __construct() {
		$this->notificationTypes = [
			'activities' => 0,
			'notifications' => 1,
			'email' => 2,
		];
	}

	private function isTypeSet(int $value, string $notificationType) : bool {
		$offset = $this->notificationTypes[$notificationType];
		return ($value & (1 << $offset)) > 0;
	}

	private function getTypeMask(bool $booleanValue, string $notificationType) : int {
		$offset = $this->notificationTypes[$notificationType];
		return ((int)$booleanValue) << $offset;
	}

	/**
	 * @param int $value an integer with bit encoded notification types
	 * @return bool returns if the activities notifications are set
	 */
	public function getActivities(int $value) : bool {
		return $this->isTypeSet($value, 'activities');
	}

	/**
	 * @param int $value an integer with bit encoded notification types
	 * @return bool returns if nextcloud notifications are set
	 */
	public function getNotifications(int $value) : bool {
		return $this->isTypeSet($value, 'notifications');
	}

	/**
	 * @param int $value an integer with bit encoded notification types
	 * @return bool returns if the email notification is set
	 */
	public function getEmail(int $value) : bool {
		return $this->isTypeSet($value, 'email');
	}

	/**
	 * @param bool $activities set activities as notification type
	 * @param bool $notificiations set nextcloud notifications as notification type
	 * @param bool $email set email as notification type
	 * @return int an integer with bit encoded notification types
	 */
	public function setNotificationTypes(bool $activities, bool $notifications, bool $email) : int {
		$value = 0;
		$value += $this->getTypeMask($activities, 'activities');
		$value += $this->getTypeMask($notifications, 'notifications');
		$value += $this->getTypeMask($email, 'email');
		return $value;
	}
}
