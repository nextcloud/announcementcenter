<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, Joas Schilling <nickvergessen@owncloud.com>
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\AnnouncementCenter\Tests;

use OCA\AnnouncementCenter\Manager;
use OCP\IGroupManager;
use OCP\IUserSession;

/**
 * Class ManagerTest
 *
 * @package OCA\AnnouncementCenter\Tests\Lib
 * @group DB
 */
class ManagerTest extends TestCase {

	/** @var Manager */
	protected $manager;

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	protected function setUp() {
		parent::setUp();

		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->manager = new Manager(
			\OC::$server->getDatabaseConnection(),
			$this->groupManager,
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
		$this->manager->getAnnouncement(0);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 2
	 */
	public function testAnnounceNoSubject() {
		$this->manager->announce('', '', '', 0, []);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedMessage Invalid subject
	 * @expectedCode 1
	 */
	public function testAnnounceSubjectTooLong() {
		$this->manager->announce(str_repeat('a', 513), '', '', 0, []);
	}

	protected function setUserGroups($groups) {
		if ($groups === null) {
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn(null);
		} else {
			$user = $this->getMockBuilder('OCP\IUser')
				->disableOriginalConstructor()
				->getMock();
			$this->userSession->expects($this->any())
				->method('getUser')
				->willReturn($user);
			$this->groupManager->expects($this->any())
				->method('getUserGroupIds')
				->with($user)
				->willReturn($groups);
		}
	}

	public function testAnnouncement() {
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

		$this->assertEquals([], $this->manager->getAnnouncements());

		$announcement = $this->manager->announce($subject, $message, $author, $time, []);
		$announcement2 = $this->manager->announce($subject, $message, $author, $time, ['gid1', 'gid2']);
		$this->assertInternalType('int', $announcement['id']);
		$this->assertGreaterThan(0, $announcement['id']);
		$this->assertSame('subject &lt;html&gt;', $announcement['subject']);
		$this->assertSame('message<br />&lt;html&gt;', $announcement['message']);
		$this->assertSame('author', $announcement['author']);
		$this->assertSame($time, $announcement['time']);

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));
		try {
			$this->assertEquals($announcement2, $this->manager->getAnnouncement($announcement2['id']));
			$this->fail('Failed to check permissions for the announcement');
		} catch (\InvalidArgumentException $e) {
			$this->assertInstanceOf('InvalidArgumentException', $e);
		}
		$this->assertEquals($announcement2, $this->manager->getAnnouncement($announcement2['id'], true, true));

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		$this->assertEquals([$announcement], $this->manager->getAnnouncements());

		$this->assertEquals(['everyone'], $this->manager->getGroups($announcement['id']));
		$this->manager->delete($announcement['id']);
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

	public function testAnnouncementGroupMember() {
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
		$this->setUserGroups(['gid1', 'gid2']);

		$this->assertEquals([], $this->manager->getAnnouncements());

		$announcement = $this->manager->announce($subject, $message, $author, $time, []);
		$announcement2 = $this->manager->announce($subject, $message, $author, $time, ['gid1', 'gid2']);
		$this->assertInternalType('int', $announcement['id']);
		$this->assertGreaterThan(0, $announcement['id']);
		$this->assertSame('subject &lt;html&gt;', $announcement['subject']);
		$this->assertSame('message<br />&lt;html&gt;', $announcement['message']);
		$this->assertSame('author', $announcement['author']);
		$this->assertSame($time, $announcement['time']);

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		$this->assertEquals($announcement, $this->manager->getAnnouncement($announcement['id']));

		$this->assertEquals([$announcement, $announcement2], $this->manager->getAnnouncements());
		$this->assertEquals([$announcement], $this->manager->getAnnouncements(15, $announcement2['id']));

		$this->assertEquals(['everyone'], $this->manager->getGroups($announcement['id']));
		$this->manager->delete($announcement['id']);
		$this->assertEquals([], $this->manager->getGroups($announcement['id']));

		try {
			$this->manager->getAnnouncement($announcement['id']);
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

		$announcement = $this->manager->announce($subject, $message, $author, $time, ['gid0', 'gid1', 'gid2']);
		$this->assertEquals(['gid1', 'gid2'], $this->manager->getGroups($announcement['id']));
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

		$announcement = $this->manager->announce($subject, $message, $author, $time, ['gid0']);
		$this->assertEquals(['everyone'], $this->manager->getGroups($announcement['id']));
		$this->manager->delete($announcement['id']);
		$this->assertEquals([], $this->manager->getGroups($announcement['id']));
	}
}
