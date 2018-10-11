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

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\BackgroundJob;
use OCA\AnnouncementCenter\Manager;
use OCP\Activity\IManager;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\IUser;
use OCP\IGroup;
use OCP\Activity\IEvent;
use OCP\Notification\INotification;

/**
 * Class PageController
 *
 * @package OCA\AnnouncementCenter\Tests\Controller
 * @group DB
 */
class BackgroundJobTest extends TestCase {
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activityManager;
	/** @var INotificationManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->activityManager = $this->createMock(IManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->manager = $this->createMock(Manager::class);
	}

	protected function getJob(array $methods = []) {
		if (empty($methods)) {
			return new BackgroundJob(
				$this->userManager,
				$this->groupManager,
				$this->activityManager,
				$this->notificationManager,
				$this->urlGenerator,
				$this->manager
			);
		} else {
			return $this->getMockBuilder(BackgroundJob::class)
				->setConstructorArgs([
					$this->userManager,
					$this->groupManager,
					$this->activityManager,
					$this->notificationManager,
					$this->urlGenerator,
					$this->manager,
				])
				->setMethods($methods)
				->getMock();
		}
	}

	public function dataRun() {
		return [
			[23, true, true, new \InvalidArgumentException()],
			[42, true, false, ['id' => 42, 'author' => 'user', 'time' => 123456789, 'groups' => ['gid1', 'gid2']]],
			[42, false, true, ['id' => 42, 'author' => 'user', 'time' => 123456789, 'groups' => ['everyone']]],
		];
	}

	/**
	 * @dataProvider dataRun
	 * @param int $id
	 * @param bool $activities
	 * @param bool $notifications
	 * @param \Exception|array|null $getResult
	 */
	public function testRun($id, $activities, $notifications, $getResult) {
		$job = $this->getJob(['createPublicity']);

		if ($getResult instanceof \Exception) {
			$this->manager->expects($this->once())
				->method('getAnnouncement')
				->with($id, false)
				->willThrowException($getResult);

			$job->expects($this->never())
				->method('createPublicity');
		} else {
			$this->manager->expects($this->once())
				->method('getAnnouncement')
				->with($id, false)
				->willReturn($getResult);

			$job->expects($this->once())
				->method('createPublicity')
				->with($getResult['id'], $getResult['author'], $getResult['time'], $getResult['groups'], [
					'id' => $id,
					'activities' => $activities,
					'notifications' => $notifications,
				]);
		}

		self::invokePrivate($job, 'run', [[
			'id' => $id,
			'activities' => $activities,
			'notifications' => $notifications,
		]]);

	}

	/**
	 * @param string $uid
	 * @param string $displayName
	 * @param bool $loggedIn
	 * @return \OCP\IUser|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getUserMock($uid, $displayName, $loggedIn = true) {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);
		$user->expects($this->any())
			->method('getLastLogin')
			->willReturn($loggedIn ? 1234 : 0);
		return $user;
	}

	protected function getGroupMock($users) {
		$group = $this->createMock(IGroup::class);
		$group->expects($this->any())
			->method('getUsers')
			->willReturn($users);
		return $group;
	}

	public function dataCreatePublicity() {
		return [
			[['everyone'], true, [
				'activities' => true,
				'notifications' => false,
			]],
			[['gid1', 'gid2'], false, [
				'activities' => false,
				'notifications' => true,
			]],
		];
	}

	/**
	 * @dataProvider dataCreatePublicity
	 * @param string[] $groups
	 * @param bool $everyone
	 * @param array $publicity
	 */
	public function testCreatePublicity(array $groups, $everyone, array $publicity) {
		$event = $this->createMock(IEvent::class);
		$event->expects($this->once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$event->expects($this->once())
			->method('setType')
			->with('announcementcenter')
			->willReturnSelf();
		$event->expects($this->once())
			->method('setAuthor')
			->with('author')
			->willReturnSelf();
		$event->expects($this->once())
			->method('setTimestamp')
			->with(1337)
			->willReturnSelf();
		$event->expects($this->once())
			->method('setSubject')
			->with('announcementsubject', ['author' => 'author', 'announcement' => 10])
			->willReturnSelf();
		$event->expects($this->once())
			->method('setMessage')
			->with('announcementmessage', [])
			->willReturnSelf();
		$event->expects($this->once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$dateTime = new \DateTime();
		$dateTime->setTimestamp(1337);
		$notification->expects($this->once())
			->method('setDateTime')
			->with($dateTime)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setSubject')
			->with('announced', ['author'])
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setLink')
			->willReturnSelf();

		$job = $this->getJob([
			'createPublicityEveryone',
			'createPublicityGroups',
		]);

		if ($everyone) {
			$job->expects($this->once())
				->method('createPublicityEveryone')
				->with('author', $event, $notification, $publicity);
		} else {
			$job->expects($this->once())
				->method('createPublicityGroups')
				->with('author', $event, $notification, $groups, $publicity);
		}

		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);

		$this->invokePrivate($job, 'createPublicity', [10, 'author', 1337, $groups, $publicity]);
	}

	public function dataCreatePublicityEveryoneAndGroup() {
		return [
			[[
				'activities' => true,
				'notifications' => false,
			], true, false],
			[[
				'activities' => false,
				'notifications' => true,
			], false, true],
		];
	}

	/**
	 * @dataProvider dataCreatePublicityEveryoneAndGroup
	 *
	 * @param array $publicity
	 * @param bool $activities
	 * @param bool $notifications
	 */
	public function testCreatePublicityEveryone(array $publicity, $activities, $notifications) {
		$event = $this->createMock(IEvent::class);
		$event->expects($activities ? $this->exactly(5) : $this->never())
			->method('setAffectedUser')
			->willReturnSelf();

		$notification = $this->createMock(INotification::class);
		$notification->expects($notifications ? $this->exactly(4) : $this->never())
			->method('setUser')
			->willReturnSelf();

		$job = $this->getJob();
		$this->userManager->expects($this->once())
			->method('callForSeenUsers')
			->with($this->anything())
			->willReturnCallback(function($callback) {
				$users = [
					$this->getUserMock('author', 'User One'),
					$this->getUserMock('u2', 'User Two'),
					$this->getUserMock('u3', 'User Three'),
					$this->getUserMock('u4', 'User Four'),
					$this->getUserMock('u5', 'User Five'),
				];
				foreach ($users as $user) {
					$callback($user);
				}
			})
		;

		$this->activityManager->expects($activities ? $this->exactly(5) : $this->never())
			->method('publish');
		$this->notificationManager->expects($notifications ? $this->exactly(4) : $this->never())
			->method('notify');

		$this->invokePrivate($job, 'createPublicityEveryone', ['author', $event, $notification, $publicity]);
	}

	/**
	 * @dataProvider dataCreatePublicityEveryoneAndGroup
	 *
	 * @param array $publicity
	 * @param bool $activities
	 * @param bool $notifications
	 */
	public function testCreatePublicityGroups(array $publicity, $activities, $notifications) {
		$event = $this->createMock(IEvent::class);
		$event->expects($activities ? $this->exactly(4) : $this->never())
			->method('setAffectedUser')
			->willReturnSelf();

		$notification = $this->createMock(INotification::class);
		$notification->expects($notifications ? $this->exactly(3) : $this->never())
			->method('setUser')
			->willReturnSelf();

		$job = $this->getJob();
		$this->groupManager->expects($this->exactly(4))
			->method('get')
			->willReturnMap([
				['gid0', null],
				['gid1', $this->getGroupMock([])],
				['gid2', $this->getGroupMock([
					$this->getUserMock('author', 'User One'),
					$this->getUserMock('u2', 'User Two'),
					$this->getUserMock('u3', 'User Three'),
				])],
				['gid3', $this->getGroupMock([
					$this->getUserMock('u3', 'User Three'),
					$this->getUserMock('u4', 'User Four', false),
					$this->getUserMock('u5', 'User Five'),
				])],
			]);

		$this->activityManager->expects($activities ? $this->exactly(4) : $this->never())
			->method('publish');
		$this->notificationManager->expects($notifications ? $this->exactly(3) : $this->never())
			->method('notify');

		$this->invokePrivate($job, 'createPublicityGroups', ['author', $event, $notification, ['gid0', 'gid1', 'gid2', 'gid3'], $publicity]);
	}
}
