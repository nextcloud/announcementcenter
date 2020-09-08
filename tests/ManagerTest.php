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
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ManagerTest
 *
 * @package OCA\AnnouncementCenter\Tests\Lib
 * @group DB
 */
class ManagerTest extends TestCase {

	/** @var Manager */
	protected $manager;

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;

	/** @var AnnouncementMapper|MockObject */
	protected $announcementMapper;

	/** @var GroupMapper|MockObject */
	protected $groupMapper;

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var INotificationManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;

	/** @var ICommentsManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $commentsManager;

	/** @var IJobList|\PHPUnit_Framework_MockObject_MockObject */
	protected $jobList;

	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->announcementMapper = $this->createMock(AnnouncementMapper::class);
		$this->groupMapper = $this->createMock(GroupMapper::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->manager = new Manager(
			$this->config,
			$this->announcementMapper,
			$this->groupMapper,
			$this->groupManager,
			$this->notificationManager,
			$this->commentsManager,
			$this->jobList,
			$this->userSession
		);

		$query = \OC::$server->getDatabaseConnection()->getQueryBuilder();
		$query->delete('announcements')->execute();
		$query->delete('announcements_groups')->execute();
	}

	/**
	 * @expectedException \OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException
	 * @expectedMessage Invalid ID
	 */
	public function testGetAnnouncementNotExist(): void {
		$this->announcementMapper->expects($this->once())
			->method('getById')
			->with(42)
			->willThrowException(new DoesNotExistException('Entity does not exist'));
		$this->manager->getAnnouncement(42);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 2
	 */
	public function testAnnounceNoSubject(): void {
		$this->manager->announce('', '', '', 0, [], false);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 1
	 */
	public function testAnnounceSubjectTooLong(): void {
		$this->manager->announce(str_repeat('a', 513), '', '', 0, [], false);
	}

	public function testDelete(): void {
		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setObject')
			->with('announcement', 23)
			->willReturnSelf();

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$this->commentsManager->expects($this->once())
			->method('deleteCommentsAtObject')
			->with('announcement', $this->identicalTo('23'));

		$announcement = $this->createMock(Announcement::class);
		$this->announcementMapper->expects($this->once())
			->method('getById')
			->with(23)
			->willReturn($announcement);
		$this->announcementMapper->expects($this->once())
			->method('delete')
			->with($announcement);
		$this->groupMapper->expects($this->once())
			->method('deleteGroupsForAnnouncement')
			->with($announcement);

		$this->manager->delete(23);
	}

	protected function getUserMock($uid) {
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}

	protected function setUserGroups($groups) {
		if ($groups === null) {
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn(null);
		} else {
			$user = $this->getUserMock('uid');
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn($user);
			$this->groupManager->expects($this->any())
				->method('getUserGroupIds')
				->with($user)
				->willReturn($groups);
		}
	}

	public function dataAnnouncementGroups() {
		return [
			[['everyone']],
			[['gid1', 'gid2']],
		];
	}

	/**
	 * @dataProvider dataAnnouncementGroups
	 * @param array $groups
	 */
	public function testAnnouncementGroups(array $groups) {
		/** @var Announcement $announcement */
		$announcement = Announcement::fromParams([]);

		$this->groupMapper->expects($this->once())
			->method('getGroupsForAnnouncement')
			->willReturn($groups);

		$this->assertSame($groups, $this->manager->getGroups($announcement));
	}

	public function dataHasNotifications(): array {
		return [
			[23, false, true, 0, true],
			[42, true, true, 0, true],
			[72, false, false, 0, false],
			[128, false, false, 55, true],
		];
	}

	/**
	 * @dataProvider dataHasNotifications
	 * @param int $id
	 * @param bool $hasActivityJob
	 * @param bool $hasNotificationJob
	 * @param int $numNotifications
	 */
	public function testHasNotifications(int $id, bool $hasActivityJob, bool $hasNotificationJob, int $numNotifications): void {
		$this->jobList->expects($hasActivityJob ? $this->once() : $this->exactly(2))
			->method('has')
			->willReturnMap([
				[BackgroundJob::class, [
					'id' => $id,
					'activities' => true,
					'notifications' => true,
				], $hasActivityJob && $hasNotificationJob],
				[BackgroundJob::class, [
					'id' => $id,
					'activities' => false,
					'notifications' => true,
				], $hasNotificationJob],
			]);

		if (!$hasNotificationJob) {
			$notification = $this->createMock(INotification::class);
			$notification->expects($this->once())
				->method('setApp')
				->with('announcementcenter')
				->willReturnSelf();
			$notification->expects($this->once())
				->method('setObject')
				->with('announcement', $id)
				->willReturnSelf();

			$this->notificationManager->expects($this->once())
				->method('createNotification')
				->willReturn($notification);
			$this->notificationManager->expects($this->once())
				->method('getCount')
				->with($notification)
				->willReturn($numNotifications);
		} else {
			$this->notificationManager->expects($this->never())
				->method('createNotification');
			$this->notificationManager->expects($this->never())
				->method('getCount');
		}

		/** @var Announcement $announcement */
		$announcement = Announcement::fromParams([
			'id' => $id,
		]);
		$this->manager->hasNotifications($announcement);
	}

	public function dataRemoveNotifications(): array {
		return [
			[23, false],
			[42, true],
		];
	}

	/**
	 * @dataProvider dataRemoveNotifications
	 * @param int $id
	 * @param bool $hasActivity
	 */
	public function testRemoveNotifications(int $id, bool $hasActivity): void {
		$this->jobList->expects($this->once())
			->method('has')
			->with(BackgroundJob::class, [
				'id' => $id,
				'activities' => true,
				'notifications' => true,
			])
			->willReturn($hasActivity);

		if ($hasActivity) {
			$this->jobList->expects($this->once())
				->method('remove')
				->with(BackgroundJob::class, [
					'id' => $id,
					'activities' => true,
					'notifications' => true,
				]);
			$this->jobList->expects($this->once())
				->method('add')
				->with(BackgroundJob::class, [
					'id' => $id,
					'activities' => true,
					'notifications' => false,
				]);
		} else {
			$this->jobList->expects($this->once())
				->method('remove')
				->with(BackgroundJob::class, [
					'id' => $id,
					'activities' => false,
					'notifications' => true,
				]);
		}

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$notification->expects($this->once())
			->method('setObject')
			->with('announcement', $id)
			->willReturnSelf();

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->once())
			->method('markProcessed')
			->with($notification);

		$this->manager->removeNotifications($id);
	}

	public function dataCheckIsAdmin() {
		return [
			[
				['admin'],
				[
					['uid', 'admin', true],
				],
				true,
			],
			[
				['admin'],
				[
					['uid', 'admin', false],
				],
				false,
			],
			[
				['admin', 'gid1'],
				[
					['uid', 'admin', false],
					['uid', 'gid1', false],
				],
				false,
			],
			[
				['admin', 'gid1'],
				[
					['uid', 'admin', false],
					['uid', 'gid1', true],
				],
				true,
			],
			[
				['admin', 'gid1'],
				[
					['uid', 'admin', true],
				],
				true,
			],
		];
	}

	/**
	 * @dataProvider dataCheckIsAdmin
	 * @param string[] $adminGroups
	 * @param array $inGroupMap
	 * @param bool $expected
	 */
	public function testCheckIsAdmin($adminGroups, $inGroupMap, $expected) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->with('announcementcenter', 'admin_groups', '["admin"]')
			->willReturn(json_encode($adminGroups));

		$user = $this->getUserMock('uid');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->groupManager->expects($this->exactly(sizeof($inGroupMap)))
			->method('isInGroup')
			->willReturnMap($inGroupMap);

		$this->assertEquals($expected, $this->manager->checkIsAdmin());
	}

	public function testCheckIsAdminNoUser() {
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn(null);

		$this->groupManager->expects($this->never())
			->method('isInGroup');

		$this->assertEquals(false, $this->manager->checkIsAdmin());
	}

	protected function assertDeleteMetaData($id) {
		$notification = $this->createMock(INotification::class);
		$notification->expects($this->at(0))
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$notification->expects($this->at(1))
			->method('setObject')
			->with('announcement', $id)
			->willReturnSelf();

		$this->notificationManager->expects($this->at(0))
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->at(1))
			->method('markProcessed')
			->with($notification);

		$this->commentsManager->expects($this->at(0))
			->method('deleteCommentsAtObject')
			->with('announcement', $id);
	}

	protected function assertHasNotification($calls = 1, $offset = 0) {
		$this->jobList->expects($this->at($offset + 0))
			->method('has')
			->willReturn(false);
		$this->jobList->expects($this->at($offset + 1))
			->method('has')
			->willReturn(false);

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->at(0))
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$notification->expects($this->at(1))
			->method('setObject')
			->with('announcement', $this->anything())
			->willReturnSelf();

		$this->notificationManager->expects($this->at($offset + 0))
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->at($offset + 1))
			->method('getCount')
			->with($notification)
			->willReturn(0);

		if ($calls > 1) {
			$this->assertHasNotification($calls - 1, $offset + 2);
		}
	}
}
