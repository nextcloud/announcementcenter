<?php
/**
 * ownCloud - AnnouncementCenter App
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AnnouncementCenter\Tests\Controller;

use OC\Notification\IManager as INotificationManager;
use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IManager;
use OCP\AppFramework\Http;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

class PageController extends TestCase {
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;
	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activityManager;
	/** @var INotificationManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var Manager|\PHPUnit_Framework_MockObject_MockObject */
	protected $manager;

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
		$this->activityManager = $this->getMockBuilder('OCP\Activity\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->notificationManager = $this->getMockBuilder('OC\Notification\IManager')
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
		$this->urlGenerator = $this->getMockBuilder('OCP\IURLGenerator')
			->disableOriginalConstructor()
			->getMock();
		$this->manager = $this->getMockBuilder('OCA\AnnouncementCenter\Manager')
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
				$this->activityManager,
				$this->notificationManager,
				$this->l,
				$this->urlGenerator,
				$this->manager,
				'author'
			);
		} else {
			return $this->getMockBuilder('OCA\AnnouncementCenter\Controller\PageController')
				->setConstructorArgs([
					'announcementcenter',
					$this->request,
					\OC::$server->getDatabaseConnection(),
					$this->groupManager,
					$this->userManager,
					$this->activityManager,
					$this->notificationManager,
					$this->l,
					$this->urlGenerator,
					$this->manager,
					'author',
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
			[0, [], [], 0, []],
			[1, [], [], 0, []],
			[2, [], [], 5, []],
			[3, [], [], 10, []],
			[
				1,
				[
					['id' => 1337, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				], [], 0,
				[
					['id' => 1337, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				],
			],
			[
				1,
				[
					['id' => 23, 'author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				],
				[
					['author1', $this->getUserMock('author1', 'Author One')],
				],
				0,
				[
					['id' => 23, 'author' => 'Author One', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				],
			],
			[
				1,
				[
					['id' => 42, 'author' => 'author1', 'subject' => "Subject &lt;html&gt;#1&lt;/html&gt;", 'message' => "Message<br />&lt;html&gt;#1&lt;/html&gt;", 'time' => 1440672792],
				], [], 0,
				[
					['id' => 42, 'author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject &lt;html&gt;#1&lt;/html&gt;', 'message' => 'Message<br />&lt;html&gt;#1&lt;/html&gt;', 'time' => 1440672792],
				],
			],
		];
	}

	/**
	 * @dataProvider dataGet
	 * @param int $page
	 * @param array $announcements
	 * @param array $userMap
	 * @param int $offset
	 * @param array $expected
	 */
	public function testGet($page, $announcements, $userMap, $offset, $expected) {
		$this->userManager->expects($this->any())
			->method('get')
			->willReturnMap($userMap);

		$this->manager->expects($this->any())
			->method('getAnnouncements')
			->with(5, $offset)
			->willReturn($announcements);

		$controller = $this->getController();
		$jsonResponse = $controller->get($page);

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $jsonResponse);
		$this->assertEquals($expected, $jsonResponse->getData());
	}

	public function dataDelete() {
		return [
			[42],
			[1337],
		];
	}

	/**
	 * @dataProvider dataDelete
	 * @param int $id
	 */
	public function testDelete($id) {

		$notification = $this->getMockBuilder('OC\Notification\INotification')
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
			->method('markProcessed');

		$this->manager->expects($this->once())
			->method('delete')
			->with($id);

		$controller = $this->getController();
		$response = $controller->delete($id);

		$this->assertInstanceOf('OCP\AppFramework\Http\Response', $response);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
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
			->method('announce')
			->with($subject, '', 'author', $this->anything())
			->willThrowException(new \InvalidArgumentException());

		$controller = $this->getController(['createPublicity']);
		$controller->expects($this->never())
			->method('createPublicity');

		$response = $controller->add($subject, '');

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
		$this->assertSame($expectedData, $response->getData());
	}

	public function dataAdd() {
		return [
			['', '', true, ['error' => 'The subject is too long or empty']],
			['subject', 'message', false, []],
		];
	}

	public function testAdd() {
		$this->manager->expects($this->once())
			->method('announce')
			->with('subject', 'message', 'author', $this->anything())
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

		$controller = $this->getController(['createPublicity']);
		$controller->expects($this->once())
			->method('createPublicity')
			->with(10, 'author', $this->anything());

		$response = $controller->add('subject', 'message');

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

	public function testCreatePublicity() {
		$event = $this->getMockBuilder('OCP\Activity\IEvent')
			->disableOriginalConstructor()
			->getMock();
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
			->with('announcementsubject#10', ['author'])
			->willReturnSelf();
		$event->expects($this->once())
			->method('setMessage')
			->with('announcementmessage#10', ['author'])
			->willReturnSelf();
		$event->expects($this->once())
			->method('setObject')
			->with('announcement', 10)
			->willReturnSelf();
		$event->expects($this->exactly(5))
			->method('setAffectedUser')
			->willReturnSelf();

		$notification = $this->getMockBuilder('OC\Notification\INotification')
			->disableOriginalConstructor()
			->getMock();
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
		$notification->expects($this->exactly(4))
			->method('setUser')
			->willReturnSelf();

		$controller = $this->getController();
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($notification);
		$this->userManager->expects($this->once())
			->method('search')
			->with('')
			->willReturn([
				$this->getUserMock('author', 'User One'),
				$this->getUserMock('u2', 'User Two'),
				$this->getUserMock('u3', 'User Three'),
				$this->getUserMock('u4', 'User Four'),
				$this->getUserMock('u5', 'User Five'),
			]);

		$this->activityManager->expects($this->exactly(5))
			->method('publish');
		$this->notificationManager->expects($this->exactly(4))
			->method('notify');

		$this->invokePrivate($controller, 'createPublicity', [10, 'author', 1337]);
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
		$this->groupManager->expects($this->once())
			->method('isAdmin')
			->with('author')
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
