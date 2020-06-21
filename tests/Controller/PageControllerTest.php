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

namespace OCA\AnnouncementCenter\Tests\Controller;

use OCA\AnnouncementCenter\Controller\PageController;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Model\Announcement;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Http;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IUser;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroup;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class PageController
 *
 * @package OCA\AnnouncementCenter\Tests\Controller
 * @group DB
 */
class PageControllerTest extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;
	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IJobList|\PHPUnit_Framework_MockObject_MockObject */
	protected $jobList;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var ITimeFactory|MockObject */
	protected $timeFactory;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->manager = $this->createMock(Manager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->userSession = $this->createMock(IUserSession::class);

		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});
	}

	protected function getController(array $methods = []): PageController {
		if (empty($methods)) {
			return new PageController(
				'announcementcenter',
				$this->request,
				\OC::$server->getDatabaseConnection(),
				$this->groupManager,
				$this->userManager,
				$this->jobList,
				$this->l,
				$this->manager,
				$this->config,
				$this->timeFactory,
				$this->userSession
			);
		}

		/** @var PageController|MockBuilder $mock */
		$mock = $this->getMockBuilder(PageController::class);
		return $mock->setConstructorArgs([
				'announcementcenter',
				$this->request,
				\OC::$server->getDatabaseConnection(),
				$this->groupManager,
				$this->userManager,
				$this->jobList,
				$this->l,
				$this->manager,
				$this->config,
				$this->timeFactory,
				$this->userSession,
			])
			->setMethods($methods)
			->getMock();
	}

	protected function getUserMock(string $uid, string $displayName): IUser {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);
		return $user;
	}

	public function dataGet(): array {
		return [
			[0, [], [], []],
			[1, [], [], []],
			[2, [], [], []],
			[
				1,
				[
					['id' => 1337, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				], [],
				[
					['id' => 1337, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				],
			],
			[
				1,
				[
					['id' => 23, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				],
				[
					['author1', $this->getUserMock('author1', 'Author One')],
				],
				[
					['id' => 23, 'author' => 'Author One', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1'], 'comments' => false],
				],
			],
			[
				1,
				[
					['id' => 42, 'author' => 'author1', 'subject' => "Subject\n<html>#1</html>", 'message' => "Message\n<html>#1</html>", 'time' => 1440672792, 'groups' => null, 'comments' => 31],
				],
				[],
				[
					['id' => 42, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject <html>#1</html>', 'message' => 'Message<br />&lt;html&gt;#1&lt;/html&gt;', 'time' => 1440672792, 'groups' => null, 'comments' => 31],
				],
			],
		];
	}

	/**
	 * @dataProvider dataGet
	 * @param int $offset
	 * @param array $announcements
	 * @param array $userMap
	 * @param array $expected
	 */
	public function legacyTestGet($offset, $announcements, $userMap, $expected) {
		$this->userManager->expects($this->any())
			->method('get')
			->willReturnMap($userMap);

		$comments = [];
		$announcements = array_map(function(array $data) use (&$comments) {

			$announcement = new Announcement();
			$announcement->setId($data['id']);
			$announcement->setUser($data['author']);
			$announcement->setSubject($data['subject']);
			$announcement->setMessage($data['message']);
			$announcement->setTime($data['time']);
			$announcement->setAllowComments($data['comments'] === false ? 0 : 1);

			if ($data['comments'] !== false) {
				$comments[] = [$announcement, $data['comments']];
			}

			return $announcement;
		}, $announcements);

		$this->manager->expects($this->any())
			->method('getAnnouncements')
			->with($offset)
			->willReturn($announcements);

		$this->manager->expects($this->exactly(count($comments)))
			->method('getNumberOfComments')
			->willReturnMap($comments);

		$controller = $this->getController();
		$jsonResponse = $controller->get($offset);

		$this->assertEquals($expected, $jsonResponse->getData());
	}

	public function dataDelete(): array {
		return [
			[42, true, Http::STATUS_OK],
			[1337, false, Http::STATUS_FORBIDDEN],
		];
	}

	/**
	 * @dataProvider dataDelete
	 * @param int $id
	 * @param bool $isAdmin
	 * @param int $statusCode
	 */
	public function testDelete($id, $isAdmin, $statusCode) {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn($isAdmin);

		if ($isAdmin) {
			$this->manager->expects($this->once())
				->method('delete')
				->with($id);
		} else {
			$this->manager->expects($this->never())
				->method('delete');
		}

		$controller = $this->getController();
		$response = $controller->delete($id);

		$this->assertEquals($statusCode, $response->getStatus());
	}

	public function dataAddThrows() {
		return [
			['', ['error' => 'The subject is too long or empty']],
			[str_repeat('a', 513), ['error' => 'The subject is too long or empty']],
		];
	}

	/**
	 * @dataProvider dataAddThrows
	 * @param string $subject
	 * @param array $expectedData
	 */
	public function testAddThrows($subject, array $expectedData) {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn(true);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($this->getUserMock('author', 'author'));

		$this->manager->expects($this->once())
			->method('announce')
			->with($subject, '', 'author', $this->anything())
			->willThrowException(new \InvalidArgumentException());

		$controller = $this->getController(['createPublicity']);
		$controller->expects($this->never())
			->method('createPublicity');

		$response = $controller->add($subject, '', [], true, true, true);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame($expectedData, $response->getData());
	}

	public function testAddNoAdmin() {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn(false);

		$this->manager->expects($this->never())
			->method('announce');
		$this->jobList->expects($this->never())
			->method('add');

		$controller = $this->getController(['createPublicity']);
		$controller->expects($this->never())
			->method('createPublicity');

		$response = $controller->add('subject', '', [], true, true, true);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function dataAdd() {
		return [
			['subject1', 'message1', ['gid1'], true, true, false],
			['subject2', 'message2', ['gid2'], true, false, false],
			['subject3', 'message3', ['gid3'], false, true, false],
			['subject4', 'message4', ['gid4'], false, false, false],
			['subject4', 'message4', ['gid4'], false, false, true],
		];
	}

	/**
	 * @dataProvider dataAdd
	 *
	 * @param string $subject
	 * @param string $message
	 * @param array $groups
	 * @param bool $activities
	 * @param bool $notifications
	 * @param bool $comments
	 */
	public function legacyTestAdd($subject, $message, array $groups, $activities, $notifications, $comments) {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn(true);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($this->getUserMock('author', 'author'));

		$this->manager->expects($this->once())
			->method('announce')
			->with($subject, $message, 'author', $this->anything(), $groups, $comments)
			->willReturn([
				'author' => 'author',
				'subject' => $subject,
				'message' => $message,
				'time' => time(),
				'id' => 10,
				'comments' => $comments,
			]);
		$this->userManager->expects($this->once())
			->method('get')
			->with('author')
			->willReturn($this->getUserMock('author', 'Author'));
		$this->jobList->expects(($activities || $notifications) ? $this->once() : $this->never())
			->method('add')
			->with('OCA\AnnouncementCenter\BackgroundJob', [
				'id' => 10,
				'activities' => $activities,
				'notifications' => $notifications,
			]);

		$controller = $this->getController();

		$response = $controller->add($subject, $message, $groups, $activities, $notifications, $comments);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$data = $response->getData();
		$this->assertArrayHasKey('time', $data);
		$this->assertInternalType('int', $data['time']);
		unset($data['time']);
		$this->assertEquals([
			'author' => 'Author',
			'author_id' => 'author',
			'subject' => $subject,
			'message' => $message,
			'id' => 10,
			'comments' => $comments,
			'notifications' => $notifications,
		], $data);
	}

	public function dataIndex(): array {
		return [
			[true, 'yes', true, 'no', false, 'no', false],
			[false, 'no', false, 'yes', true, 'yes', true],
			[false, 'no', false, 'no', false, 'yes', true],
		];
	}

	/**
	 * @dataProvider dataIndex
	 * @param bool $isAdmin
	 * @param string $createActivitiesConfig
	 * @param bool $createActivities
	 * @param string $createNotificationsConfig
	 * @param bool $createNotifications
	 * @param string $allowCommentsConfig
	 * @param bool $allowComments
	 */
	public function testIndex(bool $isAdmin, string $createActivitiesConfig, bool $createActivities, string $createNotificationsConfig, bool $createNotifications, string $allowCommentsConfig, bool $allowComments) {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn($isAdmin);
		$this->config->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['announcementcenter', 'create_activities', 'yes', $createActivitiesConfig],
				['announcementcenter', 'create_notifications', 'yes', $createNotificationsConfig],
				['announcementcenter', 'allow_comments', 'yes', $allowCommentsConfig],
			]);

		$controller = $this->getController();
		$response = $controller->index();

		$this->assertSame(
			[
				'isAdmin' => $isAdmin,
				'createActivities' => $createActivities,
				'createNotifications' => $createNotifications,
				'allowComments' => $allowComments,
			],
			$response->getParams()
		);
	}

	protected function getGroupMock(string $gid): IGroup {
		/** @var IGroup|MockObject $group */
		$group = $this->createMock(IGroup::class);

		$group->expects($this->any())
			->method('getGID')
			->willReturn($gid);

		return $group;
	}

	public function dataSearchGroup(): array {
		return [
			[true, 'gid', [], [], Http::STATUS_OK],
			[true, 'gid', [$this->getGroupMock('gid1'), $this->getGroupMock('gid2')], ['gid1', 'gid2'], Http::STATUS_OK],
			[false, '', null, ['message' => 'Logged in user must be an admin'], Http::STATUS_FORBIDDEN],
		];
	}

	/**
	 * @dataProvider dataSearchGroup
	 * @param bool $isAdmin
	 * @param string $pattern
	 * @param array|null $groupSearch
	 * @param string $expected
	 * @param int $code
	 */
	public function testSearchGroup(bool $isAdmin, string $pattern, $groupSearch, array $expected, int $code) {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn($isAdmin);

		if ($groupSearch !== null) {
			$this->groupManager->expects($this->once())
				->method('search')
				->willReturn($groupSearch);
		} else {
			$this->groupManager->expects($this->never())
				->method('search');
		}

		$controller = $this->getController();
		$response = $controller->searchGroups($pattern);
		$this->assertSame($code, $response->getStatus());
		$this->assertSame($expected, $response->getData());
	}
}
