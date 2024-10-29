<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
