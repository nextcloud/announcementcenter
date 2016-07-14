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

namespace OCA\AnnouncementCenter\Tests\Controller;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\AppFramework\Http;
use OCP\BackgroundJob\IJobList;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;

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
	/** @var INotificationManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	protected function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();
		$this->notificationManager = $this->getMockBuilder('OCP\Notification\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});
		$this->jobList = $this->getMockBuilder('OCP\BackgroundJob\IJobList')
			->disableOriginalConstructor()
			->getMock();
		$this->manager = $this->getMockBuilder('OCA\AnnouncementCenter\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->userSession = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
	}

	protected function getController(array $methods = []) {
		if (empty($methods)) {
			return new \OCA\AnnouncementCenter\Controller\PageController(
				'announcementcenter',
				$this->request,
				\OC::$server->getDatabaseConnection(),
				$this->groupManager,
				$this->userManager,
				$this->jobList,
				$this->notificationManager,
				$this->l,
				$this->manager,
				$this->userSession
			);
		} else {
			return $this->getMockBuilder('OCA\AnnouncementCenter\Controller\PageController')
				->setConstructorArgs([
					'announcementcenter',
					$this->request,
					\OC::$server->getDatabaseConnection(),
					$this->groupManager,
					$this->userManager,
					$this->jobList,
					$this->notificationManager,
					$this->l,
					$this->manager,
					$this->userSession,
				])
				->setMethods($methods)
				->getMock();
		}
	}

	protected function getUserMock($uid, $displayName) {
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();
		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);
		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);
		return $user;
	}

	public function dataGet() {
		return [
			[0, [], [], []],
			[1, [], [], []],
			[2, [], [], []],
			[
				1,
				[
					['id' => 1337, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1']],
				], [],
				[
					['id' => 1337, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1']],
				],
			],
			[
				1,
				[
					['id' => 23, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1']],
				],
				[
					['author1', $this->getUserMock('author1', 'Author One')],
				],
				[
					['id' => 23, 'author' => 'Author One', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792, 'groups' => ['gid1']],
				],
			],
			[
				1,
				[
					['id' => 42, 'author' => 'author1', 'subject' => "Subject &lt;html&gt;#1&lt;/html&gt;", 'message' => "Message<br />&lt;html&gt;#1&lt;/html&gt;", 'time' => 1440672792, 'groups' => null],
				],
				[],
				[
					['id' => 42, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject &lt;html&gt;#1&lt;/html&gt;', 'message' => 'Message<br />&lt;html&gt;#1&lt;/html&gt;', 'time' => 1440672792, 'groups' => null],
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
	public function testGet($offset, $announcements, $userMap, $expected) {
		$this->userManager->expects($this->any())
			->method('get')
			->willReturnMap($userMap);

		$this->manager->expects($this->any())
			->method('getAnnouncements')
			->with(5, $offset)
			->willReturn($announcements);

		$controller = $this->getController();
		$jsonResponse = $controller->get($offset);

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $jsonResponse);
		$this->assertEquals($expected, $jsonResponse->getData());
	}

	public function dataDelete() {
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
			$notification = $this->getMockBuilder('OCP\Notification\INotification')
				->disableOriginalConstructor()
				->getMock();
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

			$this->manager->expects($this->once())
				->method('delete')
				->with($id);
		} else {
			$this->notificationManager->expects($this->never())
				->method('markProcessed');

			$this->manager->expects($this->never())
				->method('delete');
		}

		$controller = $this->getController();
		$response = $controller->delete($id);

		$this->assertInstanceOf('OCP\AppFramework\Http\Response', $response);
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

		$response = $controller->add($subject, '', []);

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
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

		$response = $controller->add('subject', '', []);

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}

	public function dataAdd() {
		return [
			['', '', true, ['error' => 'The subject is too long or empty']],
			['subject', 'message', false, []],
		];
	}

	/**
	 * @dataProvider dataAdd
	 */
	public function testAdd() {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn(true);
		$this->userSession->expects($this->once())
			->method('getUser')
			->willReturn($this->getUserMock('author', 'author'));

		$this->manager->expects($this->once())
			->method('announce')
			->with('subject', 'message', 'author', $this->anything(), ['gid1'])
			->willReturn([
				'author' => 'author',
				'subject' => 'subject',
				'message' => 'message',
				'time' => time(),
				'id' => 10,
			]);
		$this->userManager->expects($this->once())
			->method('get')
			->with('author')
			->willReturn($this->getUserMock('author', 'Author'));
		$this->jobList->expects($this->once())
			->method('add')
			->with('OCA\AnnouncementCenter\BackgroundJob', ['id' => 10]);

		$controller = $this->getController();

		$response = $controller->add('subject', 'message', ['gid1']);

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
		$data = $response->getData();
		$this->assertArrayHasKey('time', $data);
		$this->assertInternalType('int', $data['time']);
		unset($data['time']);
		$this->assertEquals([
			'author' => 'Author',
			'author_id' => 'author',
			'subject' => 'subject',
			'message' => 'message',
			'id' => 10,
		], $data);
	}

	public function dataIndex() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider dataIndex
	 * @param bool $isAdmin
	 */
	public function testIndex($isAdmin) {
		$this->manager->expects($this->once())
			->method('checkIsAdmin')
			->willReturn($isAdmin);

		$controller = $this->getController();
		$response = $controller->index();
		$this->assertInstanceOf('OCP\AppFramework\Http\TemplateResponse', $response);

		$this->assertSame(
			[
				'is_admin' => $isAdmin,
			],
			$response->getParams()
		);
	}
}
