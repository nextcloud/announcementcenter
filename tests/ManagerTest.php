<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Model\AnnouncementDoesNotExistException;
use OCA\AnnouncementCenter\Model\AnnouncementMapper;
use OCA\AnnouncementCenter\Model\GroupMapper;
use OCA\AnnouncementCenter\Model\NotificationType;
use OCA\AnnouncementCenter\NotificationQueueJob;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ManagerTest
 *
 * @package OCA\AnnouncementCenter\Tests\Lib
 * @group DB
 */
class ManagerTest extends TestCase {
	protected IConfig&MockObject $config;
	protected AnnouncementMapper&MockObject $announcementMapper;
	protected GroupMapper&MockObject $groupMapper;
	protected IGroupManager&MockObject $groupManager;
	protected INotificationManager&MockObject $notificationManager;
	protected ICommentsManager&MockObject $commentsManager;
	protected IJobList&MockObject $jobList;
	protected IUserSession&MockObject $userSession;
	protected NotificationType&MockObject $notificationType;
	protected Manager $manager;

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
		$this->notificationType = $this->createMock(NotificationType::class);

		$this->manager = new Manager(
			$this->config,
			$this->announcementMapper,
			$this->groupMapper,
			$this->groupManager,
			$this->notificationManager,
			$this->commentsManager,
			$this->jobList,
			$this->userSession,
			$this->notificationType
		);

		$query = \OCP\Server::get(IDBConnection::class)->getQueryBuilder();
		$query->delete('announcements')->executeStatement();
		$query->delete('announcements_map')->executeStatement();
	}

	public function testGetAnnouncementNotExist(): void {
		$this->announcementMapper->expects(self::once())
			->method('getById')
			->with(42)
			->willThrowException(new DoesNotExistException('Entity does not exist'));
		$this->expectException(AnnouncementDoesNotExistException::class);
		$this->expectExceptionMessage('Announcement does not exist');
		$this->manager->getAnnouncement(42);
	}

	public function testAnnounceNoSubject(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid subject');
		$this->expectExceptionCode(2);
		$this->manager->announce('', '', '', '', 0, [], false, 7);
	}

	public function testAnnounceSubjectTooLong(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid subject');
		$this->expectExceptionCode(1);
		$this->manager->announce(str_repeat('a', 513), '', '', '', 0, [], false, 7);
	}

	public function testDelete(): void {
		$notification = $this->createMock(INotification::class);
		$notification->expects(self::once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setObject')
			->with('announcement', 23)
			->willReturnSelf();

		$this->notificationManager->expects(self::once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects(self::once())
			->method('markProcessed')
			->with($notification);

		$this->commentsManager->expects(self::once())
			->method('deleteCommentsAtObject')
			->with('announcement', $this->identicalTo('23'));

		$announcement = $this->createMock(Announcement::class);
		$this->announcementMapper->expects(self::once())
			->method('getById')
			->with(23)
			->willReturn($announcement);
		$this->announcementMapper->expects(self::once())
			->method('delete')
			->with($announcement);
		$this->groupMapper->expects(self::once())
			->method('deleteGroupsForAnnouncement')
			->with($announcement);

		$this->manager->delete(23);
	}

	protected function getUserMock($uid) {
		$user = $this->createMock(IUser::class);
		$user
			->method('getUID')
			->willReturn($uid);
		return $user;
	}

	protected function setUserGroups($groups) {
		if ($groups === null) {
			$this->userSession
				->method('getUser')
				->willReturn(null);
		} else {
			$user = $this->getUserMock('uid');
			$this->userSession
				->method('getUser')
				->willReturn($user);
			$this->groupManager
				->method('getUserGroupIds')
				->with($user)
				->willReturn($groups);
		}
	}

	public static function dataAnnouncementGroups(): array {
		return [
			[['everyone']],
			[['gid1', 'gid2']],
		];
	}

	#[DataProvider('dataAnnouncementGroups')]
	public function testAnnouncementGroups(array $groups) {
		/** @var Announcement $announcement */
		$announcement = Announcement::fromParams([]);

		$this->groupMapper->expects(self::once())
			->method('getGroupsForAnnouncement')
			->willReturn($groups);

		self::assertSame($groups, $this->manager->getGroups($announcement));
	}

	public static function dataHasNotifications(): array {
		return [
			[23, false, true, false, 0],
			[42, true, true, true, 0],
			[72, false, false, false, 0],
			[128, false, false, false, 55],
		];
	}

	#[DataProvider('dataHasNotifications')]
	public function testHasNotifications(int $id, bool $hasActivityJob, bool $hasNotificationJob, bool $hasEmailJob, int $numNotifications): void {
		$this->jobList
			->method('has')
			->willReturnMap([
				[NotificationQueueJob::class, ['id' => $id, 'activities' => true, 'notifications' => true, 'emails' => true], $hasActivityJob && $hasNotificationJob && $hasEmailJob],
				[NotificationQueueJob::class, ['id' => $id, 'activities' => false, 'notifications' => true, 'emails' => true], !$hasActivityJob && $hasNotificationJob && $hasEmailJob],
				[NotificationQueueJob::class, ['id' => $id, 'activities' => true, 'notifications' => true, 'emails' => false], $hasActivityJob && $hasNotificationJob && !$hasEmailJob],
				[NotificationQueueJob::class, ['id' => $id, 'activities' => false, 'notifications' => true, 'emails' => false], !$hasActivityJob && $hasNotificationJob && !$hasEmailJob],
			]);

		if (!$hasNotificationJob) {
			$notification = $this->createMock(INotification::class);
			$notification->expects(self::once())
				->method('setApp')
				->with('announcementcenter')
				->willReturnSelf();
			$notification->expects(self::once())
				->method('setObject')
				->with('announcement', $id)
				->willReturnSelf();

			$this->notificationManager->expects(self::once())
				->method('createNotification')
				->willReturn($notification);
			$this->notificationManager->expects(self::once())
				->method('getCount')
				->with($notification)
				->willReturn($numNotifications);
		} else {
			$this->notificationManager->expects(self::never())
				->method('createNotification');
			$this->notificationManager->expects(self::never())
				->method('getCount');
		}

		/** @var Announcement $announcement */
		$announcement = Announcement::fromParams([
			'id' => $id,
		]);
		$this->manager->hasNotifications($announcement);
	}

	public static function dataRemoveNotifications(): array {
		return [
			[23, false, false],
			[42, true, true],
		];
	}

	#[DataProvider('dataRemoveNotifications')]
	public function testRemoveNotifications(int $id, bool $hasActivity, bool $hasEmail): void {
		$this->jobList
			->method('has')
			->willReturnMap([
				[NotificationQueueJob::class, ['id' => $id, 'activities' => false, 'notifications' => true, 'emails' => false], !$hasActivity],
				[NotificationQueueJob::class, ['id' => $id, 'activities' => true, 'notifications' => true, 'emails' => true], $hasActivity],
			]);

		if ($hasActivity) {
			$this->jobList->expects(self::once())
				->method('remove')
				->with(NotificationQueueJob::class, [
					'id' => $id,
					'activities' => $hasActivity,
					'notifications' => true,
					'emails' => $hasEmail,
				]);
			$this->jobList->expects(self::once())
				->method('add')
				->with(NotificationQueueJob::class, [
					'id' => $id,
					'activities' => $hasActivity,
					'notifications' => false,
					'emails' => $hasEmail,
				]);
		} else {
			$this->jobList->expects(self::once())
				->method('remove')
				->with(NotificationQueueJob::class, [
					'id' => $id,
					'activities' => $hasActivity,
					'notifications' => true,
					'emails' => $hasEmail,
				]);
		}

		$notification = $this->createMock(INotification::class);
		$notification->expects(self::once())
			->method('setApp')
			->with('announcementcenter')
			->willReturnSelf();
		$notification->expects(self::once())
			->method('setObject')
			->with('announcement', $id)
			->willReturnSelf();

		$this->notificationManager->expects(self::once())
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects(self::once())
			->method('markProcessed')
			->with($notification);

		$this->manager->removeNotifications($id);
	}

	public static function dataCheckIsAdmin(): array {
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

	#[DataProvider('dataCheckIsAdmin')]
	public function testCheckIsAdmin(array $adminGroups, array $inGroupMap, bool $expected) {
		$this->config
			->method('getAppValue')
			->with('announcementcenter', 'admin_groups', '["admin"]')
			->willReturn(json_encode($adminGroups));

		$user = $this->getUserMock('uid');
		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->groupManager->expects(self::exactly(sizeof($inGroupMap)))
			->method('isInGroup')
			->willReturnMap($inGroupMap);

		self::assertEquals($expected, $this->manager->checkIsAdmin());
	}

	public function testCheckIsAdminNoUser(): void {
		$this->userSession
			->method('getUser')
			->willReturn(null);

		$this->groupManager->expects(self::never())
			->method('isInGroup');

		self::assertEquals(false, $this->manager->checkIsAdmin());
	}

	protected function assertDeleteMetaData($id): void {
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

	protected function assertHasNotification(int $calls = 1, int $offset = 0): void {
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
			->with('announcement', self::anything())
			->willReturnSelf();

		$this->notificationManager->expects($this->at($offset + 0))
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->at($offset + 1))
			->method('getCount')
			->with($notification)
			->willReturn(0);

		if ($calls > 1) {
			self::assertHasNotification($calls - 1, $offset + 2);
		}
	}
}
