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

use OCA\AnnouncementCenter\Manager;
use OCP\BackgroundJob\IJobList;
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\IUser;

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

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->commentsManager = $this->createMock(ICommentsManager::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->manager = new Manager(
			$this->config,
			\OC::$server->getDatabaseConnection(),
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
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid ID
	 */
	public function testGetAnnouncementNotExist() {
		$this->config->expects($this->atLeastOnce())
			->method('getAppValue')
			->with('announcementcenter', 'admin_groups', '["admin"]')
			->willReturn('["admin"]');

		$this->manager->getAnnouncement(0);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 2
	 */
	public function testAnnounceNoSubject() {
		$this->manager->announce('', '', '', 0, [], false);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 1
	 */
	public function testAnnounceSubjectTooLong() {
		$this->manager->announce(str_repeat('a', 513), '', '', 0, [], false);
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

	public function dataAnnouncement() {
		return [
			[null, true, false],
			[['gid1', 'gid2'], true, true],
			[['admin'], false, true],
		];
	}

	/**
	 * @dataProvider dataAnnouncement
	 * @param string[] $groups
	 * @param bool $noGroupsSet
	 * @param bool $canAccessBoth
	 */
	public function testAnnouncement($groups, $noGroupsSet, $canAccessBoth) {
		$this->config->expects($this->atLeastOnce())
			->method('getAppValue')
			->with('announcementcenter', 'admin_groups', '["admin"]')
			->willReturn('["admin"]');

		$subject = 'subject' . "\n<html>";
		$message = 'message' . "\n<html>";
		$author = 'author';
		$time = time() - 10;

		$this->groupManager->expects($this->exactly(2))
			->method('groupExists')
			->willReturnMap([
				['gid1', true],
				['gid2', true],
			]);
		$this->setUserGroups($groups);

		$this->assertEquals([], $this->manager->getAnnouncements());

		$this->assertHasNotification();
		$announcement = $this->manager->announce($subject, $message, $author, $time, [], false);
		$this->assertHasNotification();
		$announcement2 = $this->manager->announce($subject, $message, $author, $time + 2, ['gid1', 'gid2'], true);
		if ($noGroupsSet) {
			$announcement['groups'] = null;
			$announcement2['groups'] = null;
		}

		$this->assertInternalType('int', $announcement['id']);
		$this->assertGreaterThan(0, $announcement['id']);
		$this->assertSame('subject &lt;html&gt;', $announcement['subject']);
		$this->assertSame('message' . "\n&lt;html>", $announcement['message']);
		$this->assertSame('author', $announcement['author']);
		$this->assertSame($time, $announcement['time']);
		$this->assertFalse($announcement['comments']);
		$this->assertFalse($announcement['notifications']);

		if (!is_array($groups) || !in_array('admin', $groups, true)) {
			unset($announcement['notifications']);
			if (is_array($groups)) {
				unset($announcement2['notifications']);
			}
		} else {
			$this->assertHasNotification();
		}

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));
		if ($canAccessBoth) {
			if (is_array($groups) && in_array('admin', $groups, true)) {
				$this->assertHasNotification();
			}
			$this->assertEquals($announcement2, $this->manager->getAnnouncement($announcement2['id']));
		} else {
			try {
				$this->assertEquals($announcement2, $this->manager->getAnnouncement($announcement2['id']));
				$this->fail('Failed to check permissions for the announcement');
			} catch (\InvalidArgumentException $e) {
				$this->assertInstanceOf('InvalidArgumentException', $e);
			}
			$this->assertHasNotification();
			$this->assertEquals(
				array_merge($announcement2, ['groups' => ['gid1', 'gid2']]),
				$this->manager->getAnnouncement($announcement2['id'], true, true)
			);
			$this->assertSame(0, $announcement2['comments']);
		}

		if (is_array($groups) && in_array('admin', $groups, true)) {
			$this->assertHasNotification();
		}
		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		$this->commentsManager->expects($this->any())
			->method('getNumberOfCommentsForObject')
			->willReturn(0);

		if ($canAccessBoth) {
			if (is_array($groups) && in_array('admin', $groups, true)) {
				$this->assertHasNotification(2);
			}
			$this->assertEquals([$announcement2['id'] => $announcement2, $announcement['id'] => $announcement], $this->manager->getAnnouncements());
			if (is_array($groups) && in_array('admin', $groups, true)) {
				$this->assertHasNotification();
			}
			$this->assertEquals([$announcement['id'] => $announcement], $this->manager->getAnnouncements(15, $announcement2['id']));
			$this->assertEquals([], $this->manager->getAnnouncements(15, $announcement['id']));
		} else {
			$this->assertEquals([$announcement['id'] => $announcement], $this->manager->getAnnouncements());
			$this->assertEquals([$announcement['id'] => $announcement], $this->manager->getAnnouncements(15, $announcement2['id']));
			$this->assertEquals([], $this->manager->getAnnouncements(15, $announcement['id']));
		}

		$this->assertEquals(['everyone'], $this->manager->getGroups($announcement['id']));
		$this->assertDeleteMetaData($announcement['id']);
		$this->manager->delete($announcement['id']);
		$this->assertDeleteMetaData($announcement2['id']);
		$this->manager->delete($announcement2['id']);
		$this->assertEquals([], $this->manager->getGroups($announcement['id']));

		try {
			$this->manager->getAnnouncement($announcement['id']);
			$this->fail('Failed to delete the announcement');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}

		try {
			$this->manager->getAnnouncement($announcement2['id'], true, true);
			$this->fail('Failed to delete the announcement');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}
	}

	public function testAnnouncementGroups() {
		$subject = 'subject' . "\n<html>";
		$message = 'message' . "\n<html>";
		$author = 'author';
		$time = time() - 10;

		$this->groupManager->expects($this->exactly(3))
			->method('groupExists')
			->willReturnMap([
				['gid0', false],
				['gid1', true],
				['gid2', true],
			]);

		$this->assertHasNotification();
		$announcement = $this->manager->announce($subject, $message, $author, $time, ['gid0', 'gid1', 'gid2'], true);
		$this->assertEquals(['gid1', 'gid2'], $this->manager->getGroups($announcement['id']));
		$this->assertDeleteMetaData($announcement['id']);
		$this->manager->delete($announcement['id']);
		$this->assertEquals([], $this->manager->getGroups($announcement['id']));
	}

	public function testAnnouncementGroupsAllInvalid() {
		$subject = 'subject' . "\n<html>";
		$message = 'message' . "\n<html>";
		$author = 'author';
		$time = time() - 10;

		$this->groupManager->expects($this->exactly(1))
			->method('groupExists')
			->willReturnMap([
				['gid0', false],
			]);

		$this->assertHasNotification();
		$announcement = $this->manager->announce($subject, $message, $author, $time, ['gid0'], true);
		$this->assertEquals(['everyone'], $this->manager->getGroups($announcement['id']));
		$this->assertDeleteMetaData($announcement['id']);
		$this->manager->delete($announcement['id']);
		$this->assertEquals([], $this->manager->getGroups($announcement['id']));
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
