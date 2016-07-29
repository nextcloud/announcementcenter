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
use OCP\Comments\ICommentsManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;

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

	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$this->notificationManager = $this->getMockBuilder('OCP\Notification\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->commentsManager = $this->getMockBuilder('OCP\Comments\ICommentsManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->manager = new Manager(
			$this->config,
			\OC::$server->getDatabaseConnection(),
			$this->groupManager,
			$this->notificationManager,
			$this->commentsManager,
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
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
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

		$announcement = $this->manager->announce($subject, $message, $author, $time, [], false);
		$announcement2 = $this->manager->announce($subject, $message, $author, $time + 2, ['gid1', 'gid2'], true);
		if ($noGroupsSet) {
			$announcement['groups'] = null;
			$announcement2['groups'] = null;
		}

		$this->assertInternalType('int', $announcement['id']);
		$this->assertGreaterThan(0, $announcement['id']);
		$this->assertSame('subject &lt;html&gt;', $announcement['subject']);
		$this->assertSame('message<br />&lt;html&gt;', $announcement['message']);
		$this->assertSame('author', $announcement['author']);
		$this->assertSame($time, $announcement['time']);
		$this->assertSame(false, $announcement['comments']);

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));
		if ($canAccessBoth) {
			$this->assertEquals($announcement2, $this->manager->getAnnouncement($announcement2['id']));
		} else {
			try {
				$this->assertEquals($announcement2, $this->manager->getAnnouncement($announcement2['id']));
				$this->fail('Failed to check permissions for the announcement');
			} catch (\InvalidArgumentException $e) {
				$this->assertInstanceOf('InvalidArgumentException', $e);
			}
			$this->assertEquals(
				array_merge($announcement2, ['groups' => ['gid1', 'gid2']]),
				$this->manager->getAnnouncement($announcement2['id'], true, true)
			);
			$this->assertSame(true, $announcement2['comments']);
		}

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		if ($canAccessBoth) {
			$this->assertEquals([$announcement2['id'] => $announcement2, $announcement['id'] => $announcement], $this->manager->getAnnouncements());
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

		$announcement = $this->manager->announce($subject, $message, $author, $time, ['gid0'], true);
		$this->assertEquals(['everyone'], $this->manager->getGroups($announcement['id']));
		$this->assertDeleteMetaData($announcement['id']);
		$this->manager->delete($announcement['id']);
		$this->assertEquals([], $this->manager->getGroups($announcement['id']));
	}

	public function dataCheckIsAdmin() {
		return [
			['admin', true],
			['admin', false],
			['gid1', true],
			['gid1', false],
		];
	}

	/**
	 * @dataProvider dataCheckIsAdmin
	 * @param string $adminGroup
	 * @param bool $expected
	 */
	public function testCheckIsAdmin($adminGroup, $expected) {
		$this->config->expects($this->any())
			->method('getAppValue')
			->with('announcementcenter', 'admin_groups', '["admin"]')
			->willReturn(json_encode([$adminGroup]));

		$user = $this->getUserMock('uid');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with('uid', $adminGroup)
			->willReturn($expected);

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
		$notification = $this->getMockBuilder('OCP\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
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
}
