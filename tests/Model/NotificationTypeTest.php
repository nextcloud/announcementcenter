<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests\Model;

use OCA\AnnouncementCenter\Model\NotificationType;
use OCA\AnnouncementCenter\Tests\TestCase;

/**
 * @package OCA\AnnouncementCenter\Tests\Model
 */
class NotificationTypeTest extends TestCase {
	protected NotificationType $notificationType;

	protected function setUp(): void {
		parent::setUp();
		$this->notificationType = new NotificationType();
	}

	public function data(): array {
		return [
			// Email|Notifications|Acitivites|BitEncodedType
			[false, false, false, 0],
			[false, false, true, 1],
			[false, true, false, 2],
			[false, true, true, 3],
			[true, false, false, 4],
			[true, false, true, 5],
			[true, true, false, 6],
			[true, true, true, 7],
		];
	}

	/**
	 * @test
	 * @dataProvider data
	 */
	public function testEncode($emails, $notifications, $activities, $expected) {
		$result = $this->notificationType->setNotificationTypes($activities, $notifications, $emails);
		self::assertEquals($result, $expected);
	}

	/**
	 * @test
	 * @dataProvider data
	 */
	public function testDecode($emails, $notifications, $activities, $expected) {
		$result_activities = $this->notificationType->getActivities($expected);
		$result_notifications = $this->notificationType->getNotifications($expected);
		$result_emails = $this->notificationType->getEmail($expected);
		self::assertEquals([$result_activities, $result_notifications, $result_emails], [$activities, $notifications, $emails]);
	}
}
