<?php
/**
 * @copyright Copyright (c) 2016, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
