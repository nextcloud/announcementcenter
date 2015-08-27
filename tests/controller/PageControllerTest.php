<?php
/**
 * ownCloud - announcementcenter
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Joas Schilling <nickvergessen@gmx.de>
 * @copyright Joas Schilling 2015
 */

namespace OCA\AnnouncementCenter\Tests\Controller;

use OCA\AnnouncementCenter\Manager;
use OCA\AnnouncementCenter\Tests\TestCase;
use OCP\Activity\IManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
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
		$this->activityManager = $this->getMockBuilder('OCP\Activity\IManager')
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
			[2, [], [], 15, []],
			[3, [], [], 30, []],
			[
				1,
				[
					['author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				], [], 0,
				[
					['author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				],
			],
			[
				1,
				[
					['author' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				],
				[
					['author1', $this->getUserMock('author1', 'Author One')],
				],
				0,
				[
					['author' => 'Author One', 'author_id' => 'author1', 'subject' => 'Subject #1', 'message' => 'Message #1', 'time' => 1440672792],
				],
			],
			[
				1,
				[
					['author' => 'author1', 'subject' => 'Subject <html>#1</html>', 'message' => "Message\n<html>#1</html>", 'time' => 1440672792],
				], [], 0,
				[
					['author' => 'author1', 'author_id' => 'author1', 'subject' => 'Subject &lt;html&gt;#1&lt;/html&gt;', 'message' => 'Message<br />&lt;html&gt;#1&lt;/html&gt;', 'time' => 1440672792],
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
			->with(15, $offset)
			->willReturn($announcements);

		$controller = $this->getController();
		$jsonResponse = $controller->get($page);

		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $jsonResponse);
		$this->assertEquals($expected, $jsonResponse->getData());
	}

	public function dataAdd() {
		return [
			['', '', true, ['error' => 'The subject is too long or empty']],
			['subject', 'message', false, []],
		];
	}

	/**
	 * @dataProvider dataAdd
	 * @param string $subject
	 * @param string $message
	 * @param bool $exception
	 * @param array $expectedData
	 */
	public function testAdd($subject, $message, $exception, array $expectedData) {
		if ($exception) {
			$this->manager->expects($this->once())
				->method('announce')
				->with($subject, $message, 'author', $this->anything())
				->willThrowException(new \InvalidArgumentException());
		} else {
			$this->manager->expects($this->once())
				->method('announce')
				->with($subject, $message, 'author', $this->anything())
				->willReturn(10);
		}

		$controller = $this->getController(['publishActivities']);
		$controller->expects(($exception) ? $this->never() : $this->once())
			->method('publishActivities')
			->with(10, 'author', $this->anything());

		$response = $controller->addSubmit($subject, $message);

		$this->assertInstanceOf('OCP\AppFramework\Http\DataResponse', $response);
		$this->assertSame($expectedData, $response->getData());
	}

	public function testPublishActivities() {
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

		$controller = $this->getController();
		$this->activityManager->expects($this->once())
			->method('generateEvent')
			->willReturn($event);
		$this->userManager->expects($this->once())
			->method('search')
			->with('')
			->willReturn([
				$this->getUserMock('u1', 'User One'),
				$this->getUserMock('u2', 'User Two'),
				$this->getUserMock('u3', 'User Three'),
				$this->getUserMock('u4', 'User Four'),
				$this->getUserMock('u5', 'User Five'),
			]);

		$this->activityManager->expects($this->exactly(5))
			->method('publish');

		$this->invokePrivate($controller, 'publishActivities', [10, 'author', 1337]);
	}

	/**
	 * @param string $subject
	 * @param string $message
	 * @return DataResponse
	 */
	public function addSubmit($subject, $message) {
		$timeStamp = time();
		try {
			$id = $this->manager->announce($subject, $message, $this->userId, $timeStamp);
		} catch (\InvalidArgumentException $e) {
			return new DataResponse(
				['error' => (string) $this->l->t('The subject must not be empty.')],
				Http::STATUS_BAD_REQUEST
			);
		}

		$users = $this->userManager->search('');
		$event = $this->activityManager->generateEvent();
		$event->setApp('announcementcenter')
			->setType('announcementcenter')
			->setAffectedUser($this->userId)
			->setAuthor($this->userId)
			->setTimestamp($timeStamp)
			->setSubject('announcementsubject#' . $id, [$this->userId])
			->setMessage('announcementmessage#' . $id, [$this->userId])
			->setObject('announcement', $id);
		$this->activityManager->publish($event);

		foreach ($users as $user) {
			$event->setAffectedUser($user->getUID());
			$this->activityManager->publish($event);
		}

		return new DataResponse();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function index() {
		$jsonResponse = $this->get(1);
		return $this->templateResponse('part.content', ['announcements' => $jsonResponse->getData()]);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse
	 */
	public function add() {
		return $this->templateResponse('part.add');
	}

	/**
	 * @param string $templateFile
	 * @param array $templateData
	 * @return TemplateResponse
	 */
	protected function templateResponse($templateFile = 'part.content', array $templateData = []) {
		return new TemplateResponse('announcementcenter', 'main', array_merge([
			'user'		=> $this->userId,
			'is_admin'	=> $this->groupManager->isAdmin($this->userId),
			'template'	=> $templateFile,

			'u_add'		=> $this->urlGenerator->linkToRoute('announcementcenter.page.add'),
			'u_index'	=> $this->urlGenerator->linkToRoute('announcementcenter.page.index'),
		], $templateData));
	}
}
