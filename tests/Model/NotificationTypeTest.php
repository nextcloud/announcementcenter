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
			// Email|Notifications|Acitivites|Banner|BitEncodedType
			[false, false, false, false, 0],
			[false, false, true, false, 1],
			[false, true, false, false, 2],
			[false, true, true, false, 3],
			[true, false, false, false, 4],
			[true, false, true, false, 5],
			[true, true, false, false, 6],
			[true, true, true, false, 7],
			[false, false, false, true, 8],
			[false, false, true, true, 9],
			[false, true, false, true, 10],
			[false, true, true, true, 11],
			[true, false, false, true, 12],
			[true, false, true, true, 13],
			[true, true, false, true, 14],
			[true, true, true, true, 15],
		];
	}

	/**
	 * @test
	 * @dataProvider data
	 */
	public function testEncode($emails, $notifications, $activities, $banner, $expected) {
		$result = $this->notificationType->setNotificationTypes($activities, $notifications, $emails, $banner);
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
